<?php

namespace App\Filament\Resources\FixedAssets\Tables;

use App\Models\FixedAsset;
use App\Services\FixedAssetService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use InvalidArgumentException;

class FixedAssetsTable
{
    private const STATUS = [
        'active' => 'Aktif',
        'fully_depreciated' => 'Tersusut Penuh',
        'disposed' => 'Dilepas',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('acquisition_date')->label('Perolehan')->date()->sortable(),
                TextColumn::make('acquisition_cost')->label('Harga')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('accumulated_depreciation')->label('Akumulasi')->numeric(decimalPlaces: 2),
                TextColumn::make('book_value')
                    ->label('Nilai Buku')
                    ->state(fn (FixedAsset $record): float => $record->bookValue())
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS[$state] ?? $state)
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
            ])
            ->recordActions([
                Action::make('depreciate')
                    ->label('Penyusutan')
                    ->icon('heroicon-o-arrow-trending-down')
                    ->visible(fn (FixedAsset $record): bool => $record->status === 'active')
                    ->schema([
                        DatePicker::make('date')->label('Tanggal')->default(now())->required(),
                    ])
                    ->action(function (FixedAsset $record, array $data): void {
                        try {
                            app(FixedAssetService::class)->depreciate($record, $data['date']);
                            Notification::make()->title('Penyusutan tercatat')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
