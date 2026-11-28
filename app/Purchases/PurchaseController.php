<?php

namespace App\Purchases;

use App\CreditCards\CreditCard;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource([Purchase::class, 'creditCard']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, CreditCard $creditCard): Response
    {
        // Adding a purchase missing from a statement: back to reconciling,
        // dated in the closed period
        $reconciling = $request->query('from') === 'reconciliation';

        return Inertia::render('Purchases/Create', [
            'creditCard' => $creditCard,
            'returnTo' => $reconciling ? 'reconciliation' : null,
            'defaultKind' => $request->query('kind') === 'refund' ? 'refund' : null,
            'defaultDate' => $reconciling
                ? $creditCard->billingCycle()->lastClosing()->toDateString()
                : null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StorePurchaseRequest $request,
        CreditCard $creditCard
    ): RedirectResponse {
        $attrs = $request->validated();

        $creditCard->purchases()->create($attrs);

        if ($request->input('return_to') === 'reconciliation') {
            return to_route('credit-cards.reconciliation.show', $creditCard);
        }

        return to_route('credit-cards.show', $creditCard);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase): Response
    {
        return Inertia::render('Purchases/Edit', [
            'purchase' => $purchase,
            'creditCard' => $purchase->creditCard,
            // The attribute includes the commission of the first installment
            'baseInstallmentAmount' => $purchase->installment_count
                ? number_format((float) $purchase->getRawOriginal('installment_amount'), 2, '.', '')
                : null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdatePurchaseRequest $request,
        Purchase $purchase
    ): RedirectResponse {
        $attrs = $request->validated();

        $purchase->update($attrs);

        return to_route('credit-cards.show', $purchase->creditCard);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase): RedirectResponse
    {
        $creditCard = $purchase->creditCard;
        $purchase->delete();

        return to_route('credit-cards.show', $creditCard);
    }
}
