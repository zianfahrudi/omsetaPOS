<?php

namespace App\Services;

use App\Models\Assembly;
use App\Models\Company;
use App\Models\Material;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Perakitan: komponen diambil dari master material (stok material berkurang).
 * Produk jadi bisa dari master produk (stok bertambah, HPP rata-rata) atau diisi
 * manual (catatan biaya). Jurnal: Dr Persediaan Barang/HPP, Cr Persediaan Bahan.
 */
class AssemblyService
{
    public function __construct(
        private readonly WarehouseStockService $warehouse,
        private readonly MaterialStockService $materialStock,
        private readonly PostingService $posting,
    ) {}

    /**
     * @param  array<int, array{material_id:int, quantity:int|float}>  $components
     */
    public function create(
        Company $company,
        ?int $finishedProductId,
        ?string $finishedProductName = null,
        int $quantity = 1,
        array $components,
        Carbon|string|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): Assembly {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Jumlah produk jadi harus lebih dari nol.');
        }

        $components = array_values(array_filter(
            $components,
            fn ($c) => ! empty($c['material_id']) && (float) ($c['quantity'] ?? 0) > 0,
        ));
        if ($components === []) {
            throw new InvalidArgumentException('Perakitan harus punya minimal 1 komponen material.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $finishedProductId, $finishedProductName, $quantity, $components, $date, $notes, $createdBy) {
            $finished = null;
            if ($finishedProductId) {
                $finished = Product::query()->lockForUpdate()->findOrFail($finishedProductId);
                if (! $finished->tracksStock()) {
                    throw new InvalidArgumentException('Produk jadi (dari produk) harus berupa barang berstok.');
                }
            } elseif (blank($finishedProductName)) {
                throw new InvalidArgumentException('Pilih produk jadi atau isi nama produk jadi manual.');
            }

            $assembly = Assembly::create([
                'company_id' => $company->id,
                'product_id' => $finished?->id,
                'product_name' => $finished ? null : $finishedProductName,
                'number' => $this->number($company, $date),
                'date' => $date,
                'quantity' => $quantity,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $totalCost = 0.0;

            foreach ($components as $line) {
                $material = Material::query()->lockForUpdate()->find($line['material_id']);
                if (! $material || $material->company_id !== $company->id) {
                    throw new InvalidArgumentException('Komponen material tidak valid.');
                }

                $qty = (int) $line['quantity'];
                $unitCost = (float) $material->price;
                $lineTotal = round($unitCost * $qty, 2);

                // Kurangi stok material (validasi kecukupan + catat ledger).
                $this->materialStock->issue(
                    material: $material,
                    quantity: $qty,
                    date: $date,
                    type: 'assembly_out',
                    reference: $assembly,
                    note: "Komponen perakitan {$assembly->number}",
                    createdBy: $createdBy,
                );

                $assembly->components()->create([
                    'material_id' => $material->id,
                    'product_name' => $material->name,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);

                $totalCost += $lineTotal;
            }

            $totalCost = round($totalCost, 2);
            $unitCost = $quantity > 0 ? round($totalCost / $quantity, 2) : $totalCost;

            // Produk jadi manual → buat produk baru di master (HPP = biaya material).
            if (! $finished && filled($finishedProductName)) {
                $store = \App\Models\Store::query()->where('company_id', $company->id)->orderBy('id')->first();
                if ($store) {
                    $finished = Product::create([
                        'store_id' => $store->id,
                        'name' => $finishedProductName,
                        'sku' => 'ASM-'.str_replace('/', '-', $assembly->number),
                        'product_type' => 'goods',
                        'cost_price' => $unitCost,
                        'sell_price' => 0,
                        'stock' => 0,
                        'minimum_stock' => 0,
                        'unit' => 'pcs',
                        'is_active' => true,
                    ]);
                    $assembly->forceFill(['product_id' => $finished->id, 'product_name' => null])->save();
                }
            }

            // Produk jadi (dari master / baru dibuat) → tambah stok + HPP rata-rata tertimbang.
            if ($finished) {
                $oldStock = (int) $finished->stock;
                $oldCost = (float) $finished->cost_price;
                $newStock = $oldStock + $quantity;
                $newCost = $newStock > 0
                    ? round((($oldStock * $oldCost) + $totalCost) / $newStock, 2)
                    : $unitCost;

                $finished->forceFill(['stock' => $newStock, 'cost_price' => $newCost])->save();
                $this->warehouse->adjustDefault($finished, $quantity);

                StockMovement::create([
                    'store_id' => $finished->store_id,
                    'product_id' => $finished->id,
                    'user_id' => $createdBy,
                    'type' => 'assembly_in',
                    'quantity' => $quantity,
                    'stock_before' => $oldStock,
                    'stock_after' => $newStock,
                    'reference_type' => Assembly::class,
                    'reference_id' => $assembly->id,
                    'notes' => "Hasil perakitan {$assembly->number}",
                ]);
            }

            $assembly->forceFill(['total_cost' => $totalCost])->save();

            // Jurnal: pindahkan nilai dari Persediaan Bahan ke Persediaan Barang
            // (produk jadi) atau ke HPP (tanpa produk sama sekali).
            if ($totalCost > 0) {
                $materialInv = $company->account('material_inventory') ?? $company->account('inventory');
                $debitAcc = $finished
                    ? ($company->account('inventory') ?? $materialInv)
                    : $company->account('cogs');

                if ($materialInv && $debitAcc && $debitAcc->id !== $materialInv->id) {
                    $this->posting->post(
                        company: $company,
                        date: $date,
                        lines: [
                            ['account_id' => $debitAcc->id, 'debit' => $totalCost, 'memo' => "Perakitan {$assembly->number}"],
                            ['account_id' => $materialInv->id, 'credit' => $totalCost, 'memo' => "Pemakaian bahan {$assembly->number}"],
                        ],
                        type: 'inventory',
                        description: "Perakitan {$assembly->number} - ".$assembly->finishedName(),
                        reference: $assembly->number,
                        source: $assembly,
                        createdBy: $createdBy,
                    );
                }
            }

            ActivityLogger::log('assembly.created', "Perakitan {$assembly->number}", null, $assembly, [
                'product' => $assembly->finishedName(),
                'quantity' => $quantity,
                'total_cost' => $totalCost,
            ]);

            return $assembly->load('components', 'product');
        });
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = Assembly::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('ASM/%s/%04d', $period, $sequence);
            $sequence++;
        } while (Assembly::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
