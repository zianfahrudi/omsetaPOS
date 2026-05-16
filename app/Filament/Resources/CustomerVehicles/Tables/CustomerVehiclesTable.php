<?php

namespace App\Filament\Resources\CustomerVehicles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerVehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store.name')
                    ->label('Store')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('customer.phone')
                    ->label('No. HP')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('plate_number')
                    ->label('Nomor plat')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mileage')
                    ->label('KM terdata')
                    ->numeric()
                    ->suffix(' km')
                    ->sortable(),
                TextColumn::make('last_service_at')
                    ->label('Service terakhir')
                    ->state(fn ($record) => $record->last_service_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('last_service_mileage')
                    ->label('KM terakhir')
                    ->state(fn ($record) => $record->last_service_mileage)
                    ->numeric()
                    ->suffix(' km')
                    ->placeholder('-'),
                TextColumn::make('last_service_summary')
                    ->label('History terakhir')
                    ->state(fn ($record) => $record->last_service_summary)
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
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
