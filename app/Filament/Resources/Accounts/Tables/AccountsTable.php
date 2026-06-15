<?php

namespace App\Filament\Resources\Accounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AccountsTable
{
    private const TYPE_LABELS = [
        'asset' => 'Aset',
        'liability' => 'Liabilitas',
        'equity' => 'Ekuitas',
        'revenue' => 'Pendapatan',
        'expense' => 'Beban',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Akun')
                    ->description(fn ($record) => $record->is_postable ? null : 'Akun induk')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::TYPE_LABELS[$state] ?? $state),
                TextColumn::make('normal_balance')
                    ->label('Saldo Normal')
                    ->formatStateUsing(fn (string $state): string => $state === 'debit' ? 'Debit' : 'Kredit')
                    ->toggleable(),
                IconColumn::make('is_postable')
                    ->label('Bisa Dijurnal')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('opening_balance')
                    ->label('Saldo Awal')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Akun')
                    ->options(self::TYPE_LABELS),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
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
