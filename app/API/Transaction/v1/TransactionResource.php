<?php

namespace App\API\Transaction\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'credit_card_id' => $this->credit_card_id,
            'concept' => $this->concept,
            'datetime' => $this->datetime,
            'amount' => $this->amount,
            'deadline_months' => $this->whenNotNull($this->deadline_months),
            'commission' => $this->whenNotNull($this->comission),
            'interest_rate' => $this->whenNotNull($this->interest_rate),
            'parent_transaction_id' => $this->whenNotNull(
                $this->parent_transaction_id
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'links' => [
                'self' => route('transactions.show', $this->resource),
            ]
        ];
    }
}
