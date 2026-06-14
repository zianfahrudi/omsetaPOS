<?php

namespace App\Http\Resources;

use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Refund */
class RefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'receipt_type' => 'refund',
            'id' => $this->id,
            'number' => $this->number,
            'sale_number' => $this->sale?->number,
            'store_name' => $this->store?->name,
            'handled_by_name' => $this->handledBy?->name,
            'customer_name' => $this->sale?->customer_name ?? 'Pelanggan Umum',
            'type' => $this->type,
            'status' => $this->status,
            'reason' => $this->reason,
            'returned_total' => (float) $this->returned_total,
            'replacement_total' => (float) $this->replacement_total,
            'refund_amount' => (float) $this->refund_amount,
            'additional_payment_amount' => (float) $this->additional_payment_amount,
            'evidence_photos' => $this->evidence_photos ?? [],
            'created_at' => $this->created_at->format('d M Y H:i'),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'direction' => $item->direction,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ])->values()),
        ];
    }
}
