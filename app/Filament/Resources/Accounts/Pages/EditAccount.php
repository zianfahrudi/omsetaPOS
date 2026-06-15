<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Models\Account;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['normal_balance'] = Account::normalBalanceFor((string) $data['type']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Account $record): bool => $record->is_system),
        ];
    }
}
