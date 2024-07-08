<?php

namespace App\Purchases;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdatePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->purchase);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'concept' => 'required|string|max:255',
            'datetime' => 'required|date|before_or_equal:now',
            'applied_at' => 'date|nullable|before_or_equal:now',
            'amount' => 'required|decimal:0,2|min:1',
        ];

        if ($this->purchase->is_installment_purchase) {
            return [
                ...$rules,
                'installment_amount' => "required|decimal:0,2|min:1|lte:amount",
                'installment_count' => "required|integer",
                'commission' => "decimal:0,2|nullable",
                'interest_rate' => "decimal:0,2|nullable",
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
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
