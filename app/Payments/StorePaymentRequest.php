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
        ];
    }
}
