<?php

namespace App\Purchases;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePurchaseRequest extends FormRequest
{
    use HandlesAuthorization;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', [Purchase::class, $this->creditCard]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_installment_purchase' => $this->boolean(
                'is_installment_purchase'
            ),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $exclude = 'exclude_if:is_installment_purchase,false';

        return [
            'is_installment_purchase' => 'required|boolean',
            'concept' => 'required|string|max:255',
            'datetime' => 'required|date|before_or_equal:now',
            'applied_at' => 'date|nullable|before_or_equal:now',
            'amount' => 'required|decimal:0,2|min:1',

            'installment_amount' => "$exclude|required|decimal:0,2|min:1|lte:amount",
            'installment_count' => "$exclude|required|integer",
            'commission' => "$exclude|decimal:0,2|nullable",
            'interest_rate' => "$exclude|decimal:0,2|nullable",
        ];
    }

    public function attributes(): array
    {
        return [
            'is_installment_purchase' => __('labels.is_installment_purchase'),
            'concept' => __('labels.concept'),
            'datetime' => __('labels.datetime'),
            'applied_at' => __('fecha de aplicación'),
            'amount' => __('labels.amount'),
            'installment_amount' => __('labels.installment_amount'),
            'installment_count' => __('labels.installment_count'),
            'commission' => __('labels.commission'),
            'interest_rate' => __('labels.interest_rate'),
        ];
    }
}
