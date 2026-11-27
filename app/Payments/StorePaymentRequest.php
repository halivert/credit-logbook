<?php

namespace App\Payments;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'purchases.*' => ['required', $this->unpaidPurchaseOfThisCard(installments: false)],
            'installment_purchases' => 'sometimes|array',
            'installment_purchases.*.id' => ['required', $this->unpaidPurchaseOfThisCard(installments: true)],
            'installment_purchases.*.installment_number' => 'required|integer|min:1',
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

    /**
     * Only unpaid purchases of the card being paid can be settled, otherwise
     * a payment could mark someone else's purchases as paid.
     */
    private function unpaidPurchaseOfThisCard(bool $installments)
    {
        return Rule::exists('purchases', 'id')
            ->where('credit_card_id', $this->route('creditCard')->id)
            ->whereNull('paid_at')
            ->where(fn (Builder $query) => $installments
                ? $query->whereNotNull('installment_count')
                : $query->whereNull('installment_count'));
    }
}
