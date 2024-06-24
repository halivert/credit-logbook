<?php

namespace App\CreditCard\v1;

use App\CreditCard\CreditCard;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
     *
     * @return JsonResponse
     */
    public function store(StoreCreditCardRequest $request): JsonResponse
    {
        $attrs = $request->validated();

        $creditCard = $request->user()->creditCards()->create($attrs);

        return response()->json(
            new CreditCardResource($creditCard),
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(CreditCard $creditCard): JsonResponse
    {
        return response()->json(new CreditCardResource($creditCard));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateCreditCardRequest $request,
        CreditCard $creditCard
    ): JsonResponse {
        $attrs = $request->validated();

        $creditCard->update($attrs);

        return response()->json(new CreditCardResource($creditCard));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CreditCard $creditCard): JsonResponse
    {
        $creditCard->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
