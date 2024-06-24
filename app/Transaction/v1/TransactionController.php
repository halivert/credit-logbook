<?php

namespace App\Transaction\v1;

use App\CreditCard\CreditCard;
use App\Http\Controllers\Controller;
use App\Transaction\Transaction;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource([Transaction::class, 'creditCard']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(CreditCard $creditCard): TransactionCollection
    {
        return new TransactionCollection(
            $creditCard->transactions()->paginate(10)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        CreditCard $creditCard,
        StoreTransactionRequest $request
    ): JsonResponse {
        $attrs = $request->validated();

        $transaction = $creditCard->transactions()->create($attrs);

        return response()->json(
            new TransactionResource($transaction),
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json(new TransactionResource($transaction));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateTransactionRequest $request,
        Transaction $transaction
    ): JsonResponse {
        $transaction->update($request->validated());

        return response()->json(
            new TransactionResource($transaction),
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
