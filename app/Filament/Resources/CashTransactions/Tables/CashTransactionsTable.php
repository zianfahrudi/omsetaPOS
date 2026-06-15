<?php

namespace App\Filament\Resources\CashTransactions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashTransactionsTable
{
    private const TYPE_LABELS = [
        'in' => 'Kas Masuk',
        'out' => 'Kas Keluar',
        'transfer' => 'Transfer',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('account.name')->label('Akun')->searchable(),
                TextColumn::make('description')->label('Keterangan')->limit(40),
                TextColumn::make('amount')->label('Nominal')->numeric(decimalPlaces: 2)->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(self::TYPE_LABELS),
            ]);
    }
}
