<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Resources\Journals\JournalResource;
use App\Models\Company;
use App\Models\Journal;
use App\Services\Accounting\PostingService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(PostingService::class)->post(
                company: $company,
                date: $data['date'],
                lines: $data['lines'] ?? [],
                type: 'general',
                description: $data['description'] ?? null,
                reference: $data['reference'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->title('Jurnal gagal diposting')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        return new Journal; // unreachable, satisfies return type
    }
}
