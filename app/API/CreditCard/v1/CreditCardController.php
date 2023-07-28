<?php

namespace App\API\CreditCard\v1;

use App\API\CreditCard\CreditCard;
use App\Http\Controllers\Controller;

class CreditCardController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CreditCard::class, 'creditCard');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCreditCardRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CreditCard $creditCard)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateCreditCardRequest $request,
        CreditCard $creditCard
    ) {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CreditCard $creditCard)
    {
        //
    }
}
