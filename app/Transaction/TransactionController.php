<?php

namespace App\Transaction;

use App\CreditCard\CreditCard;
use App\Http\Controllers\Controller;
use App\Transaction\v1\StoreTransactionRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(
            [Transaction::class, 'creditCard'],
            'transaction'
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CreditCard $creditCard)
    {
        return Inertia::render('Transactions/Edit', [
            'creditCard' => $creditCard
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        CreditCard $creditCard,
        StoreTransactionRequest $request
    ) {
        $attrs = $request->validated();

        $creditCard->transactions()->create($attrs);

        return to_route('credit-cards.show', $creditCard);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
