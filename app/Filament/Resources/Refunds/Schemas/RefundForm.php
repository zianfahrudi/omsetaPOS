<?php

namespace App\Filament\Resources\Refunds\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RefundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                Select::make('sale_id')
                    ->relationship('sale', 'id')
                    ->required(),
                Select::make('handled_by_id')
                    ->relationship('handledBy', 'name'),
                TextInput::make('number')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('partial'),
                TextInput::make('status')
                    ->required()
                    ->default('completed'),
                Textarea::make('reason')
                    ->columnSpanFull(),
                TextInput::make('returned_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('replacement_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('refund_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('additional_payment_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
