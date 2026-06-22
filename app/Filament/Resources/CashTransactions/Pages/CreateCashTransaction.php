<?php

namespace App\Filament\Resources\CashTransactions\Pages;

use App\Filament\Resources\CashTransactions\CashTransactionResource;
use App\Models\CashTransaction;
use App\Models\Company;
use App\Services\CashService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateCashTransaction extends CreateRecord
{
    protected static string $resource = CashTransactionResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);
        $service = app(CashService::class);

        try {
            return match ($data['type']) {
                'in' => $service->receive($company, (int) $data['account_id'], (int) $data['counter_account_id'], (float) $data['amount'], $data['date'], $data['description'] ?? null, createdBy: auth()->id()),
                'out' => $service->pay($company, (int) $data['account_id'], (int) $data['counter_account_id'], (float) $data['amount'], $data['date'], $data['description'] ?? null, createdBy: auth()->id()),
                'transfer' => $service->transfer($company, (int) $data['account_id'], (int) $data['to_account_id'], (float) $data['amount'], $data['date'], $data['description'] ?? null, createdBy: auth()->id()),
            };
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Transaksi gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new CashTransaction;
    }
}
