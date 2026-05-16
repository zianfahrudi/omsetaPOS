<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('vehicles_summary')
                    ->label('Kendaraan')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('visit_count')
                    ->label('Transaksi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_spent')
                    ->label('Total belanja')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('outstanding_debt')
                    ->label('Hutang')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('debt_total')
                    ->label('Riwayat hutang')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_purchase_at')
                    ->label('Terakhir belanja')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_debt_at')
                    ->label('Hutang terakhir')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
