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
            'date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'applied_on' => 'nullable|date_format:Y-m-d|after_or_equal:date|before_or_equal:today',
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
            'date' => __('labels.date'),
            'applied_on' => __('labels.applied_on'),
            'amount' => __('labels.amount'),
            'installment_amount' => __('labels.installment_amount'),
            'installment_count' => __('labels.installment_count'),
            'commission' => __('labels.commission'),
            'interest_rate' => __('labels.interest_rate'),
        ];
    }

    /**
     * A return keeps being a return: the form shows its amount as positive.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();

        if ($this->purchase->is_refund) {
            $validated['amount'] = -abs($validated['amount']);
        }

        return data_get($validated, $key, $default);
    }
}
