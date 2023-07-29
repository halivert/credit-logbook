<?php

namespace App\API\CreditCard\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'due_date' => $this->due_date,
            'closing_date' => $this->closing_date,
            'interest_rate' => $this->interest_rate,
            'limit' => $this->limit,
            'deleted_at' => $this->whenNotNull($this->deleted_at),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'links' => [
                'self' => route('credit-cards.show', $this->resource),
            ]
        ];
    }
}
