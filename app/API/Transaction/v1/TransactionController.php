<?php

namespace App\API\Transaction\v1;

use App\API\CreditCard\CreditCard;
use App\API\Transaction\Transaction;
use App\Http\Controllers\Controller;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource([Transaction::class, 'credit_card']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(CreditCard $creditCard)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        CreditCard $creditCard,
        StoreTransactionRequest $request
    ) {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateTransactionRequest $request,
        Transaction $transaction
    ) {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}
