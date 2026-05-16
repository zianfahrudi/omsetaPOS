<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'store_id',
    'customer_id',
    'plate_number',
    'mileage',
    'notes',
])]
class CustomerVehicle extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'mileage' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function latestServiceSale(): HasOne
    {
        return $this->hasOne(Sale::class)->latestOfMany('id');
    }

    public function getLastServiceSummaryAttribute(): ?string
    {
        $sale = $this->relationLoaded('latestServiceSale')
            ? $this->latestServiceSale
            : $this->latestServiceSale()->with('items')->first();

        if (! $sale) {
            return null;
        }

        return $sale->items
            ->pluck('product_name')
            ->filter()
            ->implode(', ');
    }

    public function getLastServiceMileageAttribute(): ?int
    {
        $sale = $this->relationLoaded('latestServiceSale')
            ? $this->latestServiceSale
            : $this->latestServiceSale()->first();

        return $sale?->vehicle_mileage ?? $this->mileage;
    }

    public function getLastServiceAtAttribute(): mixed
    {
        $sale = $this->relationLoaded('latestServiceSale')
            ? $this->latestServiceSale
            : $this->latestServiceSale()->first();

        return $sale?->paid_at ?? $sale?->created_at;
    }
}
