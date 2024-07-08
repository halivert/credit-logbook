<?php

namespace App\CreditCards;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CreditCardController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CreditCard::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
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
    public function create(): Response
    {
        return Inertia::render('CreditCards/Edit');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCreditCardRequest $request): RedirectResponse
    {
        $attrs = $request->validated();

        $request->user()->creditCards()->create($attrs);

        return to_route('credit-cards.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(CreditCard $creditCard): Response
    {
        $purchases = $creditCard->purchases()
            ->whereNull('installment_count')
            ->orderBy('datetime', 'desc')
            ->limit(100)
            ->simplePaginate(20);

        $installmentPurchases = $creditCard->purchases()
            ->whereNotNull('installment_count')
            ->where('datetime', '>=', 'today')
            ->orderBy('datetime', 'desc')
            ->whereNull('paid_at')
            ->get();

        return Inertia::render('CreditCards/Show', [
            'creditCard' => $creditCard,
            'purchases' => $purchases,
            'installmentPurchases' => $installmentPurchases,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CreditCard $creditCard): Response
    {
        return Inertia::render('CreditCards/Edit', [
            'creditCard' => $creditCard,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCreditCardRequest $request, CreditCard $creditCard): RedirectResponse
    {
        $attrs = $request->validated();

        $creditCard->update($attrs);

        return to_route('credit-cards.show', $creditCard);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CreditCard $creditCard): void
    {
        //
    }
}
