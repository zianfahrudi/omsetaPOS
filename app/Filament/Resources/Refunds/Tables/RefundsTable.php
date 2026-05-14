<?php

namespace App\Filament\Resources\Refunds\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store.name')
                    ->searchable(),
                TextColumn::make('sale.id')
                    ->searchable(),
                TextColumn::make('handledBy.name')
                    ->searchable(),
                TextColumn::make('number')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('returned_total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('replacement_total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('refund_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('additional_payment_amount')
                    ->money('IDR')
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
            ]);
    }
}
