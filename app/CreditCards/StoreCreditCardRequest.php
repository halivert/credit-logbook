<?php

namespace App\CreditCards;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCreditCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', CreditCard::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'due_date' => 'required|integer|min:1|max:28',
            'closing_date' => 'required|integer|min:1|max:28',
            'interest_rate' => 'nullable|decimal:0,2|min:0|max:200',
            'limit' => 'required|decimal:0,2|min:0',
            'timezone' => 'required|timezone:all'
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
            'timezone' => __('labels.timezone'),
        ];
    }
}
