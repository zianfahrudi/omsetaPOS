<?php

namespace App\Filament\Resources\CashierSessions\Pages;

use App\Filament\Resources\CashierSessions\CashierSessionResource;
use App\Models\CashierSession;
use App\Models\Store;
use App\Services\CashierSessionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateCashierSession extends CreateRecord
{
    protected static string $resource = CashierSessionResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $store = Store::query()->findOrFail($data['store_id']);

        try {
            return app(CashierSessionService::class)->open(
                store: $store,
                userId: (int) auth()->id(),
                openingCash: (float) $data['opening_cash'],
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Gagal buka sesi')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new CashierSession;
    }
}
