<?php

namespace App\API\Transaction\v1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->transaction);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'concept' => 'sometimes|required|string|max:255',
            'datetime' => 'sometimes|required|date|before_or_equal:today',
            'amount' => 'sometimes|required|decimal:2,4|min:1',
            'deadline_months' => 'sometimes|nullable|integer',
            'commission' => 'sometimes|nullable|decimal:2,4',
            'interest_rate' => 'sometimes|nullable|decimal:2',
        ];
    }

    public function attributes(): array
    {
        return [
            'concept' => __('labels.concept'),
            'datetime' => __('labels.datetime'),
            'amount' => __('labels.amount'),
            'deadline_months' => __('labels.deadline_months'),
            'commission' => __('labels.commission'),
            'interest_rate' => __('labels.interest_rate'),
        ];
    }
}
