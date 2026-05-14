<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('store.name')
                    ->label('Store'),
                TextEntry::make('name'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('visit_count')
                    ->label('Jumlah transaksi')
                    ->numeric(),
                TextEntry::make('total_spent')
                    ->label('Total belanja')
                    ->money('IDR'),
                TextEntry::make('outstanding_debt')
                    ->label('Hutang berjalan')
                    ->money('IDR'),
                TextEntry::make('debt_total')
                    ->label('Total riwayat hutang')
                    ->money('IDR'),
                TextEntry::make('last_purchase_at')
                    ->label('Pembelian terakhir')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('last_debt_at')
                    ->label('Hutang terakhir')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
