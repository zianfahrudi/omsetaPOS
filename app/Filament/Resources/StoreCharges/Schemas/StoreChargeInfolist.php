<?php

namespace App\Filament\Resources\StoreCharges\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StoreChargeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('store.name')
                    ->label('Toko'),
                IconEntry::make('is_tax_active')
                    ->label('Tax aktif')
                    ->boolean(),
                TextEntry::make('tax_percentage')
                    ->label('Tax (%)')
                    ->numeric(),
                IconEntry::make('is_service_fee_active')
                    ->label('Service fee aktif')
                    ->boolean(),
                TextEntry::make('service_fee_percentage')
                    ->label('Service fee (%)')
                    ->numeric(),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
