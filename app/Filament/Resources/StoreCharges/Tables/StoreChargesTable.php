<?php

namespace App\Filament\Resources\StoreCharges\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoreChargesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store.name')
                    ->label('Toko')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_tax_active')
                    ->label('Tax')
                    ->boolean(),
                TextColumn::make('tax_percentage')
                    ->label('Tax %')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_service_fee_active')
                    ->label('Service fee')
                    ->boolean(),
                TextColumn::make('service_fee_percentage')
                    ->label('Service %')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
