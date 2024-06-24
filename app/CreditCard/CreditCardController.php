<?php

namespace App\CreditCard;

use App\CreditCard\CreditCard;
use App\CreditCard\v1\StoreCreditCardRequest;
use App\Http\Controllers\Controller;
use App\Transaction\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CreditCardController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CreditCard::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $creditCards = CreditCard::query()
            ->where('user_id', auth()->user()->id)
            ->paginate(10);

        return Inertia::render('CreditCards/Index', [
            'creditCards' => $creditCards
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('CreditCards/Edit');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCreditCardRequest $request)
    {
        $attrs = $request->validated();

        $request->user()->creditCards()->create($attrs);

        return to_route('credit-cards.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(CreditCard $creditCard)
    {
        $transactions = Transaction::query()
            ->where('credit_card_id', $creditCard->getKey())
            ->paginate(20);

        return Inertia::render('CreditCards/Show', [
            'creditCard' => $creditCard,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CreditCard $creditCard)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CreditCard $creditCard)
    {
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
