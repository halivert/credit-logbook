<?php

namespace App\Purchases;

use App\Support\Money;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePurchaseRequest extends FormRequest
{
    /** Banks in Mexico offer up to 48 months, some up to 60 */
    public const MAX_INSTALLMENTS = 60;

    use HandlesAuthorization;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', [Purchase::class, $this->creditCard]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_installment_purchase' => $this->boolean('is_installment_purchase')
                && ! $this->boolean('is_refund'),
            'is_refund' => $this->boolean('is_refund'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $exclude = 'exclude_if:is_installment_purchase,false';

        return [
            'is_installment_purchase' => 'required|boolean',
            'is_refund' => 'required|boolean',
            'concept' => 'required|string|max:255',
            'date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'applied_on' => 'nullable|date_format:Y-m-d|after_or_equal:date|before_or_equal:today',
            // Returns are positive here, they're stored as negative
            'amount' => ['required', ...Money::rules()],

            // Empty means the even split, otherwise the bank's amount is kept
            'installment_amount' => [$exclude, 'nullable', ...Money::rules(), 'lte:amount'],
            'installment_count' => [$exclude, 'required', 'integer', 'min:1', 'max:'.self::MAX_INSTALLMENTS],
            'commission' => [$exclude, 'nullable', ...Money::rules('0')],
            'interest_rate' => [$exclude, 'nullable', 'decimal:0,2', 'min:0', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'is_installment_purchase' => __('labels.is_installment_purchase'),
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
     * Returns are stored as negative amounts: a credit to the card.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();

        if ($validated['is_refund']) {
            $validated['amount'] = -abs($validated['amount']);
        }

        if ($validated['is_installment_purchase'] && blank($validated['installment_amount'] ?? null)) {
            $validated['installment_amount'] = Money::fromCents(
                InstallmentPlan::evenInstallmentCents(
                    Money::toCents($validated['amount']),
                    (int) $validated['installment_count'],
                )
            );
        }

        return data_get($validated, $key, $default);
    }

}
