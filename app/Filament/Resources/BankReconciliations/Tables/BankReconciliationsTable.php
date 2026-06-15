<?php

namespace App\Filament\Resources\BankReconciliations\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankReconciliationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('statement_date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('statement_date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('account.name')->label('Akun')->searchable(),
                TextColumn::make('statement_balance')->label('Saldo Koran')->numeric(decimalPlaces: 2),
                TextColumn::make('book_balance')->label('Saldo Buku')->numeric(decimalPlaces: 2),
                TextColumn::make('difference')->label('Selisih')->numeric(decimalPlaces: 2),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'balanced' ? 'Cocok' : 'Selisih')
                    ->color(fn (string $state): string => $state === 'balanced' ? 'success' : 'danger'),
            ]);
    }
}
