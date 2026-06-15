<?php

namespace App\Filament\Resources\BankReconciliations\Pages;

use App\Filament\Resources\BankReconciliations\BankReconciliationResource;
use App\Models\BankReconciliation;
use App\Models\Company;
use App\Services\BankReconciliationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateBankReconciliation extends CreateRecord
{
    protected static string $resource = BankReconciliationResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(BankReconciliationService::class)->reconcile(
                company: $company,
                accountId: (int) $data['account_id'],
                statementDate: $data['statement_date'],
                statementBalance: (float) $data['statement_balance'],
                notes: $data['notes'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Rekonsiliasi gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new BankReconciliation();
    }
}
