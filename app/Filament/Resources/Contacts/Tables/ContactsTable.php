<?php

namespace App\Filament\Resources\Contacts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactsTable
{
    private const TYPE_LABELS = [
        'customer' => 'Pelanggan',
        'supplier' => 'Supplier',
        'employee' => 'Karyawan',
        'other' => 'Lainnya',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::TYPE_LABELS[$state] ?? $state),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                TextColumn::make('receivable_balance')
                    ->label('Piutang')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('payable_balance')
                    ->label('Hutang')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(self::TYPE_LABELS),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
