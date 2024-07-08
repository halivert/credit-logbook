<?php

namespace App\Purchases;

use App\CreditCards\CreditCard;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
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
    public function create(CreditCard $creditCard): Response
    {
        return Inertia::render('Purchases/Create', [
            'creditCard' => $creditCard,
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
