<?php

namespace App\Payments;

use App\CreditCards\CreditCard;
use App\Http\Controllers\Controller;
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
        return Inertia::render('Payments/Create', [
            'creditCard' => $creditCard->append(['balance_used', 'current_statement']),
        ]);
    }

    public function store(
        StorePaymentRequest $request,
        CreditCard $creditCard
    ): RedirectResponse {
        $creditCard->payments()->create($request->validated());

        return to_route('credit-cards.show', $creditCard);
    }

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
