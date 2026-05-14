<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DiscountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('store.name')
                    ->label('Toko'),
                TextEntry::make('name')
                    ->label('Nama diskon'),
                TextEntry::make('code')
                    ->label('Kode diskon'),
                TextEntry::make('type')
                    ->label('Tipe'),
                TextEntry::make('value')
                    ->label('Nilai')
                    ->numeric(),
                TextEntry::make('minimum_spend')
                    ->label('Minimal belanja')
                    ->money('IDR'),
                TextEntry::make('usage_limit')
                    ->label('Kuota')
                    ->placeholder('Tanpa batas'),
                TextEntry::make('used_count')
                    ->label('Terpakai')
                    ->numeric(),
                TextEntry::make('starts_at')
                    ->label('Mulai aktif')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ends_at')
                    ->label('Berakhir')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ]);
    }
}
