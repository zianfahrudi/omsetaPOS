<?php

namespace App\Http\Resources;

use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SaleItem */
class SaleItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->product_name,
            'product_type' => $this->product_type,
            'quantity' => $this->quantity,
            'refunded_quantity' => $this->refunded_quantity,
            'refundable_quantity' => max(0, $this->quantity - $this->refunded_quantity),
            'unit_price' => (float) $this->unit_price,
            'fee_amount' => (float) $this->fee_amount,
            'service_fee_amount' => (float) $this->service_fee_amount,
            'tax_amount' => (float) $this->tax_amount,
            'line_total' => (float) $this->line_total,
        ];
    }
}
