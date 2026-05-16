<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('store.name')
                    ->searchable(),
                TextColumn::make('cashier.name')
                    ->searchable(),
                TextColumn::make('number')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('vehicle_plate_number')
                    ->label('Plat')
                    ->searchable(),
                TextColumn::make('vehicle_mileage')
                    ->label('KM')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('payment_method')
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->label('Status bayar')
                    ->state(fn ($record): string => (float) $record->debt_amount > 0 ? 'Belum lunas' : 'Lunas')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Belum lunas' ? 'warning' : 'success'),
                IconColumn::make('payment_proof')
                    ->label('Bukti')
                    ->boolean()
                    ->state(fn ($record): bool => filled($record->payment_proof)),
                TextColumn::make('subtotal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('discount_total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('discount_code')
                    ->label('Kode diskon')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tax_total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('service_fee_total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('change_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('debt_amount')
                    ->label('Nominal hutang')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
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
            ]);
    }
}
