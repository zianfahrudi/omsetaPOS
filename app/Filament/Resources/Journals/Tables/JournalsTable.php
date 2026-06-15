<?php

namespace App\Filament\Resources\Journals\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JournalsTable
{
    private const TYPE_LABELS = [
        'general' => 'Jurnal Umum',
        'sales' => 'Penjualan',
        'purchase' => 'Pembelian',
        'cash_receipt' => 'Kas Masuk',
        'cash_payment' => 'Kas Keluar',
        'inventory' => 'Persediaan',
        'opening' => 'Saldo Awal',
        'adjustment' => 'Penyesuaian',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::TYPE_LABELS[$state] ?? $state),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('total_debit')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'posted' => 'success',
                        'void' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(self::TYPE_LABELS),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
