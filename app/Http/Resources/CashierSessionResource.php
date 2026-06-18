<?php

namespace App\Http\Resources;

use App\Models\CashierSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CashierSession */
class CashierSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'store_id' => $this->store_id,
            'store_name' => $this->store?->name,
            'cashier_name' => $this->cashier?->name,
            'status' => $this->status,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'opening_cash' => (float) $this->opening_cash,
            'cash_sales_total' => (float) $this->cash_sales_total,
            'expected_cash' => (float) $this->expected_cash,
            'closing_cash' => (float) $this->closing_cash,
            'cash_difference' => (float) $this->cash_difference,
            'notes' => $this->notes,
        ];
    }
}
