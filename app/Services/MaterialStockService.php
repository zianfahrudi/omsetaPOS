<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Pergerakan stok material + harga rata-rata tertimbang.
 */
class MaterialStockService
{
    /**
     * Material masuk (pembelian/penyesuaian +). Update harga rata-rata tertimbang.
     */
    public function receive(
        Material $material,
        float $quantity,
        float $unitCost,
        Carbon|string|null $date = null,
        string $type = 'purchase_in',
        ?Model $reference = null,
        ?string $note = null,
        ?int $createdBy = null,
    ): MaterialMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Jumlah harus lebih dari nol.');
        }

        $before = (float) $material->stock;
        $after = round($before + $quantity, 2);

        // Harga rata-rata tertimbang.
        $oldValue = $before * (float) $material->price;
        $newValue = $quantity * $unitCost;
        $avg = $after > 0 ? round(($oldValue + $newValue) / $after, 2) : $unitCost;

        $material->forceFill(['stock' => $after, 'price' => $avg])->save();

        return $material->movements()->create([
            'date' => $date ? Carbon::parse($date) : now(),
            'type' => $type,
            'quantity' => round($quantity, 2),
            'stock_before' => $before,
            'stock_after' => $after,
            'unit_cost' => round($unitCost, 2),
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Material keluar (perakitan/penyesuaian -). Memakai harga material saat ini.
     */
    public function issue(
        Material $material,
        float $quantity,
        Carbon|string|null $date = null,
        string $type = 'assembly_out',
        ?Model $reference = null,
        ?string $note = null,
        ?int $createdBy = null,
    ): MaterialMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Jumlah harus lebih dari nol.');
        }

        $before = (float) $material->stock;
        if ($before < $quantity) {
            throw new InvalidArgumentException("Stok material {$material->name} tidak cukup (tersedia {$before}).");
        }

        $after = round($before - $quantity, 2);
        $material->forceFill(['stock' => $after])->save();

        return $material->movements()->create([
            'date' => $date ? Carbon::parse($date) : now(),
            'type' => $type,
            'quantity' => round(-$quantity, 2),
            'stock_before' => $before,
            'stock_after' => $after,
            'unit_cost' => (float) $material->price,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'created_by' => $createdBy,
        ]);
    }
}
