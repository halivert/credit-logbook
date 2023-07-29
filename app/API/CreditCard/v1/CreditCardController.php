<?php

namespace App\API\CreditCard\v1;

use App\API\CreditCard\CreditCard;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreditCardController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CreditCard::class, 'creditCard');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CreditCardCollection
    {
        return new CreditCardCollection(
            $request->user()->creditCards()->paginate(10)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCreditCardRequest $request): void
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(CreditCard $creditCard): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCreditCardRequest $request, CreditCard $creditCard): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CreditCard $creditCard): void
    {
        //
    }
}
