<?php

namespace App\API\CreditCard\v1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->credit_card);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'due_date' => 'sometimes|required|integer|min:1|max:28',
            'closing_date' => 'sometimes|required|integer|min:1|max:28',
            'interest_rate' => 'sometimes|nullable|decimal:2|min:0|max:100',
            'limit' => 'sometimes|required|decimal:2,4|min:0',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('labels.name'),
            'due_date' => __('labels.due_date'),
            'closing_date' => __('labels.closing_date'),
            'interest_rate' => __('labels.interest_rate'),
            'limit' => __('labels.limit'),
        ];
    }
}
