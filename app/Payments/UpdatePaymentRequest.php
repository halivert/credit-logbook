<?php

namespace App\Payments;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->payment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'datetime' => 'required|date|before_or_equal:now',
            'amount' => 'required|decimal:0,2|min:1',
            'purchases' => 'required|array',
            'purchases.*' => 'required|exists:purchases,id',
            'installment_purchases' => 'required|array',
            'installment_purchases.*.id' => 'required|exists:purchases,id',
            'installment_purchases.*.installment_number' => 'required|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'datetime' => __('labels.datetime'),
            'amount' => __('labels.amount'),
            'purchases' => __('labels.compras'),
            'installment_purchases' => __('labels.installment_purchases'),
        ];
    }
}
