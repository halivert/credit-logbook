<?php

namespace App\Payments;

use App\CreditCards\CreditCard;
use App\Http\Controllers\Controller;
use App\Purchases\Purchase;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource([Payment::class, 'creditCard']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CreditCard $creditCard): Response
    {
        $purchases = $creditCard->purchases()
            ->whereNull('installment_count')
            ->whereNull('paid_at')
            ->get();

        $installmentPurchases = $creditCard->purchases()
            ->whereNotNull('installment_count')
            ->whereNull('paid_at')
            ->get();

        return Inertia::render('Payments/Edit', [
            'payment' => new Payment,
            'creditCard' => $creditCard,
            'purchases' => $purchases,
            'installmentPurchases' => $installmentPurchases,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StorePaymentRequest $request,
        CreditCard $creditCard
    ): RedirectResponse {
        $attrs = $request->validated();

        /** @var Payment */
        $payment = $creditCard->payments()->create($attrs);

        $payment->purchases()->sync($attrs['purchases']);
        $payment->purchases()->update(['paid_at' => $attrs['datetime']]);

        $installmentPurchases = $attrs['installment_purchases'];

        foreach ($installmentPurchases as $item) {
            // #PerformanceIssue
            $purchase = Purchase::query()->find($item['id']);
            $installmentNumber = min(
                $item['installment_number'],
                $purchase->installment_count
            );

            $payment->purchases()->attach([$item['id']], [
                'installment_number' => $installmentNumber,
            ]);

            if ($purchase->installment_count === $installmentNumber) {
                $purchase->update(['paid_at' => $attrs['datetime']]);
            }
        }

        return to_route('credit-cards.show', $creditCard);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment): void
    {
        //
    }
}
