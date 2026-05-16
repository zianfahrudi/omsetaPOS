<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'cashier_id',
    'customer_id',
    'customer_vehicle_id',
    'discount_id',
    'number',
    'customer_name',
    'customer_phone',
    'vehicle_plate_number',
    'vehicle_mileage',
    'status',
    'payment_method',
    'payment_proof',
    'is_debt',
    'subtotal',
    'discount_code',
    'discount_type',
    'discount_value',
    'discount_total',
    'tax_percentage',
    'tax_total',
    'service_fee_percentage',
    'service_fee_total',
    'grand_total',
    'paid_amount',
    'change_amount',
    'debt_amount',
    'paid_at',
])]
class Sale extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'service_fee_percentage' => 'decimal:2',
            'service_fee_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'debt_amount' => 'decimal:2',
            'vehicle_mileage' => 'integer',
            'is_debt' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function productTaxTotal(): float
    {
        return $this->itemChargeTotal('tax_amount');
    }

    public function productServiceFeeTotal(): float
    {
        return $this->itemChargeTotal('service_fee_amount');
    }

    private function itemChargeTotal(string $column): float
    {
        if (! in_array($column, ['tax_amount', 'service_fee_amount'], true)) {
            return 0.0;
        }

        if ($this->relationLoaded('items')) {
            return round((float) $this->items->sum(
                fn (SaleItem $item): float => (float) $item->{$column} * (int) $item->quantity
            ), 2);
        }

        return round((float) $this->items()
            ->selectRaw("COALESCE(SUM(quantity * {$column}), 0) as total")
            ->value('total'), 2);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
