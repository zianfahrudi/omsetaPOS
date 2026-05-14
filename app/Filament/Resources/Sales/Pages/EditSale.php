<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Customer;
use App\Support\ActivityLogger;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    private float $oldDebtAmount = 0;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['payment_status'] = ((float) ($data['debt_amount'] ?? 0) > 0) ? 'belum_lunas' : 'lunas';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldDebtAmount = (float) $this->record->debt_amount;

        $paymentStatus = $data['payment_status'] ?? (((float) ($data['debt_amount'] ?? 0) > 0) ? 'belum_lunas' : 'lunas');
        $grandTotal = (float) ($data['grand_total'] ?? $this->record->grand_total);

        if ($paymentStatus === 'lunas') {
            $data['is_debt'] = (bool) $this->record->is_debt;
            $data['debt_amount'] = 0;
            $data['paid_amount'] = max((float) ($data['paid_amount'] ?? 0), $grandTotal);
            $data['change_amount'] = 0;
        } else {
            $debtAmount = max(0, (float) ($data['debt_amount'] ?? 0));
            $data['is_debt'] = true;
            $data['debt_amount'] = $debtAmount;
            $data['paid_amount'] = max(0, $grandTotal - $debtAmount);
            $data['change_amount'] = 0;
        }

        unset($data['payment_status']);

        return $data;
    }

    protected function afterSave(): void
    {
        $newDebtAmount = (float) $this->record->debt_amount;
        $delta = $newDebtAmount - $this->oldDebtAmount;

        if ($delta !== 0.0 && $this->record->customer_id) {
            DB::transaction(function () use ($delta) {
                $customer = Customer::query()->lockForUpdate()->find($this->record->customer_id);

                $customer?->forceFill([
                    'outstanding_debt' => max(0, (float) $customer->outstanding_debt + $delta),
                    'last_debt_at' => $delta > 0 ? now() : $customer->last_debt_at,
                ])->save();
            });
        }

        ActivityLogger::log('sale.payment_status_updated', "Status pembayaran {$this->record->number} diperbarui dari CMS", $this->record->store_id, $this->record, [
            'old_debt_amount' => $this->oldDebtAmount,
            'new_debt_amount' => $newDebtAmount,
        ]);
    }
}
