<?php

namespace App\CreditCards;

use App\Support\Money;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCreditCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->creditCard);
    }

    /**
     * The interest rate is optional in the form, an empty one means 0.
     */
    protected function prepareForValidation(): void
    {
        if ($this->exists('interest_rate') && blank($this->input('interest_rate'))) {
            $this->merge(['interest_rate' => 0]);
        }
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
            'interest_rate' => 'sometimes|nullable|decimal:0,2|min:0|max:200',
            'limit' => ['sometimes', 'required', ...Money::rules('0')],
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
