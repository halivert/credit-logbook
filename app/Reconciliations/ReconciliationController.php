<?php

namespace App\Reconciliations;

use App\CreditCards\BillingCycle;
use App\CreditCards\CreditCard;
use App\CreditCards\Statement;
use App\Http\Controllers\Controller;
use App\Purchases\Purchase;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Compares the latest closing with what the bank's statement says, lists
 * that period so a missing purchase is easy to spot, and records an
 * adjustment when the difference can't be found.
 */
class ReconciliationController extends Controller
{
    public function show(CreditCard $creditCard): Response
    {
        $this->authorize('update', $creditCard);

        $statement = $creditCard->statement();
        $bankStatement = $this->bankStatement($creditCard, $statement);
        $reconciliation = $bankStatement ? $this->reconciliation($statement, $bankStatement) : null;

        return Inertia::render('Reconciliations/Show', [
            'creditCard' => $creditCard,
            'statement' => $statement->toArray(),
            'bankAmount' => $bankStatement?->to_avoid_interest,
            'reconciliation' => $reconciliation ? [
                'logbook' => Money::fromCents($reconciliation->logbookCents),
                'bank' => Money::fromCents($reconciliation->bankCents),
                'difference' => Money::fromCents($reconciliation->differenceCents()),
                'matched' => $reconciliation->isMatched(),
            ] : null,
            'lines' => $this->periodLines($creditCard, $statement),
            'adjustments' => $creditCard->adjustments()->orderByDesc('date')->get(),
        ]);
    }

    public function update(Request $request, CreditCard $creditCard): RedirectResponse
    {
        $this->authorize('update', $creditCard);

        $validated = $request->validate([
            'to_avoid_interest' => ['required', ...Money::rules('0')],
        ]);

        $creditCard->bankStatements()->updateOrCreate(
            ['closing_date' => $creditCard->statement()->closing->toDateString()],
            ['to_avoid_interest' => $validated['to_avoid_interest']],
        );

        return to_route('credit-cards.reconciliation.show', $creditCard);
    }

    /**
     * The adjustment is the current difference, calculated here: the client
     * can't choose its amount.
     */
    public function storeAdjustment(CreditCard $creditCard): RedirectResponse
    {
        $this->authorize('update', $creditCard);

        $statement = $creditCard->statement();
        $bankStatement = $this->bankStatement($creditCard, $statement);

        abort_unless($bankStatement, 422, 'Captura primero lo que dice tu estado de cuenta.');

        $difference = $this->reconciliation($statement, $bankStatement)->differenceCents();

        if ($difference !== 0) {
            $creditCard->adjustments()->create([
                'date' => $statement->closing->toDateString(),
                'amount' => Money::fromCents($difference),
                'concept' => 'Ajuste de conciliación',
            ]);
        }

        return to_route('credit-cards.reconciliation.show', $creditCard);
    }

    public function destroyAdjustment(Adjustment $adjustment): RedirectResponse
    {
        $this->authorize('update', $adjustment->creditCard);

        $adjustment->delete();

        return to_route('credit-cards.reconciliation.show', $adjustment->creditCard);
    }

    private function bankStatement(CreditCard $creditCard, Statement $statement): ?BankStatement
    {
        return $creditCard->bankStatements()
            ->where('closing_date', $statement->closing->toDateString())
            ->first();
    }

    private function reconciliation(Statement $statement, BankStatement $bankStatement): Reconciliation
    {
        return Reconciliation::of(
            $statement,
            Money::toCents($bankStatement->getRawOriginal('to_avoid_interest')),
        );
    }

    /**
     * What the closed period had, like the statement lists it: purchases and
     * returns, the installments billed at its closing, adjustments and
     * payments.
     *
     * @return Collection<int, array{date: string, concept: string, amount: float, kind: string}>
     */
    private function periodLines(CreditCard $creditCard, Statement $statement): Collection
    {
        $cycle = $creditCard->billingCycle();
        $closing = $statement->closing;
        $from = $cycle->lastClosing($closing->subDay())->addDay();
        $range = [$from->toDateString(), $closing->toDateString()];

        $purchases = $creditCard->purchases()
            ->whereNull('installment_count')
            ->whereRaw('coalesce(applied_on, date) between ? and ?', $range)
            ->get()
            ->map(fn (Purchase $purchase) => [
                'date' => $purchase->day()->toDateString(),
                'concept' => $purchase->concept,
                'amount' => (float) $purchase->amount,
                'kind' => $purchase->is_refund ? 'refund' : 'purchase',
            ]);

        $installments = $creditCard->purchases()
            ->whereNotNull('installment_count')
            ->whereRaw('coalesce(applied_on, date) <= ?', [$closing->toDateString()])
            ->get()
            ->map(fn (Purchase $purchase) => $this->installmentLine($cycle, $purchase, $from, $closing))
            ->filter();

        $adjustments = $creditCard->adjustments()
            ->whereBetween('date', $range)
            ->get()
            ->map(fn (Adjustment $adjustment) => [
                'date' => $adjustment->date->toDateString(),
                'concept' => $adjustment->concept,
                'amount' => (float) $adjustment->amount,
                'kind' => 'adjustment',
            ]);

        $payments = $creditCard->payments()
            ->whereBetween('date', $range)
            ->get()
            ->map(fn ($payment) => [
                'date' => $payment->date->toDateString(),
                'concept' => 'Pago',
                'amount' => -(float) $payment->amount,
                'kind' => 'payment',
            ]);

        return $purchases->concat($installments)->concat($adjustments)->concat($payments)
            ->sortBy('date')
            ->values();
    }

    /**
     * The installment a purchase billed at the closing, if any.
     */
    private function installmentLine(BillingCycle $cycle, Purchase $purchase, $from, $closing): ?array
    {
        $plan = $purchase->installmentPlan();
        $before = min($cycle->closingsBetween($purchase->day(), $from->subDay()), $plan->count);
        $after = min($cycle->closingsBetween($purchase->day(), $closing), $plan->count);

        if ($after === $before) {
            return null;
        }

        $cents = $plan->billedCents($after) - $plan->billedCents($before)
            + ($before === 0 ? $plan->commissionCents : 0);

        return [
            'date' => $closing->toDateString(),
            'concept' => "{$purchase->concept} · mensualidad {$after} de {$plan->count}",
            'amount' => Money::fromCents($cents),
            'kind' => 'installment',
        ];
    }
}
