<?php

namespace App\Http\Resources;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sale */
class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $debtAmount = (float) $this->debt_amount;
        $unpaid = (bool) $this->is_debt && $debtAmount > 0;

        return [
            'id' => $this->id,
            'number' => $this->number,
            'store_name' => $this->store?->name,
            'cashier_name' => $this->cashier?->name,
            'customer_name' => $this->customer?->name ?? $this->customer_name ?? 'Pelanggan Umum',
            'customer_phone' => $this->customer?->phone ?? $this->customer_phone,
            'vehicle_plate_number' => $this->vehicle_plate_number,
            'vehicle_mileage' => $this->vehicle_mileage,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_proof' => $this->payment_proof,
            'payment_status' => $unpaid ? 'belum_lunas' : 'lunas',
            'payment_status_label' => $unpaid ? 'Belum lunas' : 'Lunas',
            'subtotal' => (float) $this->subtotal,
            'discount_code' => $this->discount_code,
            'discount_total' => (float) $this->discount_total,
            'tax_total' => (float) $this->tax_total,
            'service_fee_total' => (float) $this->service_fee_total,
            'grand_total' => (float) $this->grand_total,
            'paid_amount' => (float) $this->paid_amount,
            'change_amount' => (float) $this->change_amount,
            'is_debt' => (bool) $this->is_debt,
            'debt_amount' => $debtAmount,
            'paid_at' => $this->paid_at?->format('d M Y H:i') ?? $this->created_at->format('d M Y H:i'),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
