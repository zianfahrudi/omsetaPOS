<?php

namespace App\Filament\Resources\Refunds\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RefundInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('store.name')
                    ->label('Store'),
                TextEntry::make('sale.id')
                    ->label('Sale'),
                TextEntry::make('handledBy.name')
                    ->label('Handled by')
                    ->placeholder('-'),
                TextEntry::make('number'),
                TextEntry::make('type'),
                TextEntry::make('status'),
                TextEntry::make('reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('returned_total')
                    ->numeric(),
                TextEntry::make('replacement_total')
                    ->numeric(),
                TextEntry::make('refund_amount')
                    ->numeric(),
                TextEntry::make('additional_payment_amount')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
