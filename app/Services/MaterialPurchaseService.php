<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Material;
use App\Models\MaterialPurchase;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Belanja bahan (pembelian material):
 *   Dr Persediaan Bahan
 *     Cr Kas/Bank (tunai)  ATAU  Hutang Usaha (kredit)
 * Stok material bertambah + harga rata-rata tertimbang.
 */
class MaterialPurchaseService
{
    public function __construct(
        private readonly MaterialStockService $stock,
        private readonly PostingService $posting,
    ) {}

    /**
     * @param  array<int, array{material_id:int, quantity:float|int, unit_cost:float|int}>  $items
     */
    public function create(
        Company $company,
        array $items,
        string $paymentMethod = 'cash',
        ?int $contactId = null,
        Carbon|string|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): MaterialPurchase {
        if (! in_array($paymentMethod, MaterialPurchase::METHODS, true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        $clean = array_values(array_filter(
            $items,
            fn ($i) => ! empty($i['material_id']) && (float) ($i['quantity'] ?? 0) > 0,
        ));
        if ($clean === []) {
            throw new InvalidArgumentException('Belanja bahan harus punya minimal 1 item.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $clean, $paymentMethod, $contactId, $date, $notes, $createdBy) {
            $purchase = MaterialPurchase::create([
                'company_id' => $company->id,
                'contact_id' => $contactId,
                'number' => $this->number($company, $date),
                'date' => $date,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $total = 0.0;

            foreach ($clean as $line) {
                $material = Material::query()->lockForUpdate()->find($line['material_id']);
                if (! $material || $material->company_id !== $company->id) {
                    throw new InvalidArgumentException('Material tidak valid.');
                }

                $qty = (float) $line['quantity'];
                $unitCost = (float) ($line['unit_cost'] ?? 0);
                $lineTotal = round($qty * $unitCost, 2);

                $purchase->items()->create([
                    'material_id' => $material->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);

                $this->stock->receive(
                    material: $material,
                    quantity: $qty,
                    unitCost: $unitCost,
                    date: $date,
                    type: 'purchase_in',
                    reference: $purchase,
                    note: "Belanja bahan {$purchase->number}",
                    createdBy: $createdBy,
                );

                $total += $lineTotal;
            }

            $total = round($total, 2);
            $purchase->forceFill(['total' => $total])->save();

            // Jurnal: Dr Persediaan Bahan, Cr Kas/Bank atau Hutang Usaha.
            $materialInv = $company->account('material_inventory') ?? $company->account('inventory');
            $creditSubtype = match ($paymentMethod) {
                'bank' => 'bank',
                'credit' => 'accounts_payable',
                default => 'cash',
            };
            $creditAcc = $company->account($creditSubtype);

            if (! $materialInv || ! $creditAcc) {
                throw new InvalidArgumentException('Akun Persediaan Bahan / Kas / Hutang belum dikonfigurasi.');
            }

            $this->posting->post(
                company: $company,
                date: $date,
                lines: [
                    ['account_id' => $materialInv->id, 'debit' => $total, 'memo' => "Belanja bahan {$purchase->number}"],
                    ['account_id' => $creditAcc->id, 'credit' => $total, 'contact_id' => $contactId, 'memo' => "Belanja bahan {$purchase->number}"],
                ],
                type: $paymentMethod === 'credit' ? 'purchase' : 'cash_payment',
                description: "Belanja bahan {$purchase->number}",
                reference: $purchase->number,
                source: $purchase,
                createdBy: $createdBy,
            );

            ActivityLogger::log('material_purchase.created', "Belanja bahan {$purchase->number}", null, $purchase, [
                'total' => $total,
                'method' => $paymentMethod,
            ]);

            return $purchase->load('items.material');
        });
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = MaterialPurchase::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('BB/%s/%04d', $period, $sequence);
            $sequence++;
        } while (MaterialPurchase::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
