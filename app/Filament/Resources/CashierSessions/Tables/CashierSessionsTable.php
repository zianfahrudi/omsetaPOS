<?php

namespace App\Filament\Resources\CashierSessions\Tables;

use App\Models\CashierSession;
use App\Services\CashierSessionService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

class CashierSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('opened_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('store.name')->label('Toko')->searchable(),
                TextColumn::make('cashier.name')->label('Kasir')->searchable(),
                TextColumn::make('opened_at')->label('Buka')->dateTime()->sortable(),
                TextColumn::make('closed_at')->label('Tutup')->dateTime()->placeholder('-'),
                TextColumn::make('opening_cash')->label('Kas Awal')->numeric(decimalPlaces: 2),
                TextColumn::make('expected_cash')->label('Seharusnya')->numeric(decimalPlaces: 2),
                TextColumn::make('closing_cash')->label('Kas Akhir')->numeric(decimalPlaces: 2),
                TextColumn::make('cash_difference')
                    ->label('Selisih')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($state): string => (float) $state < 0 ? 'danger' : ((float) $state > 0 ? 'warning' : 'success')),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'open' ? 'Terbuka' : 'Ditutup')
                    ->color(fn (string $state): string => $state === 'open' ? 'info' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(['open' => 'Terbuka', 'closed' => 'Ditutup']),
            ])
            ->recordActions([
                Action::make('close')
                    ->label('Tutup Sesi')
                    ->icon('heroicon-o-lock-closed')
                    ->visible(fn (CashierSession $record): bool => $record->isOpen())
                    ->schema([
                        TextInput::make('closing_cash')->label('Kas Akhir (Hitung Fisik)')->numeric()->required(),
                        Textarea::make('notes')->label('Catatan'),
                    ])
                    ->action(function (CashierSession $record, array $data): void {
                        try {
                            app(CashierSessionService::class)->close($record, (float) $data['closing_cash'], $data['notes'] ?? null);
                            Notification::make()->title('Sesi ditutup')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
