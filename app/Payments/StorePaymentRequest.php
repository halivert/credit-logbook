<?php

namespace App\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StorePaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        try {
            $this->merge(['datetime' => Carbon::parse($this->datetime)]);
        } catch (\Exception $e) {
        }
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
            'purchases' => 'sometimes|array',
            'purchases.*' => 'required|exists:purchases,id',
            'installment_purchases' => 'sometimes|array',
            'installment_purchases.*.id' => 'required|exists:purchases,id',
            'installment_purchases.*.installment_number' => 'required|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'datetime' => __('labels.datetime'),
            'amount' => __('labels.amount'),
            'purchases' => __('labels.purchases'),
            'installment_purchases' => __('labels.installment_purchases'),
        ];
    }
}
