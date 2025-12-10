<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'payment_gateway' => $this->payment_gateway,
            'type' => $this->type,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status,
            'note' => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}