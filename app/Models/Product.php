<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'category_id',
    'unit_id',
    'name',
    'sku',
    'barcode',
    'image_url',
    'product_type',
    'cost_price',
    'sell_price',
    'fee_amount',
    'product_service_fee',
    'product_service_fee_type',
    'product_tax_type',
    'product_tax_value',
    'stock',
    'minimum_stock',
    'unit',
    'is_active',
])]
class Product extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'product_service_fee' => 'decimal:2',
            'product_tax_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function tracksStock(): bool
    {
        return $this->product_type !== 'service';
    }

    public function unitSalePrice(): float
    {
        return $this->baseSalePrice() + $this->productServiceFeeAmount() + $this->productTaxAmount();
    }

    public function baseSalePrice(): float
    {
        return (float) $this->sell_price + (float) $this->fee_amount;
    }

    public function productServiceFeeAmount(): float
    {
        return $this->chargeAmount(
            (string) ($this->product_service_fee_type ?: 'fixed'),
            (float) $this->product_service_fee,
            $this->baseSalePrice(),
        );
    }

    public function productTaxAmount(): float
    {
        return $this->chargeAmount(
            (string) ($this->product_tax_type ?: 'fixed'),
            (float) $this->product_tax_value,
            $this->baseSalePrice(),
        );
    }

    private function chargeAmount(string $type, float $value, float $base): float
    {
        $value = max(0, $value);

        if ($type === 'percentage') {
            return round($base * min($value, 100) / 100, 2);
        }

        return round($value, 2);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
