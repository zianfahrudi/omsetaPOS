<?php

namespace App\Filament\Resources\StoreCharges;

use App\Filament\Resources\StoreCharges\Pages\CreateStoreCharge;
use App\Filament\Resources\StoreCharges\Pages\EditStoreCharge;
use App\Filament\Resources\StoreCharges\Pages\ListStoreCharges;
use App\Filament\Resources\StoreCharges\Pages\ViewStoreCharge;
use App\Filament\Resources\StoreCharges\Schemas\StoreChargeForm;
use App\Filament\Resources\StoreCharges\Schemas\StoreChargeInfolist;
use App\Filament\Resources\StoreCharges\Tables\StoreChargesTable;
use App\Models\StoreCharge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StoreChargeResource extends Resource
{
    protected static ?string $model = StoreCharge::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static \UnitEnum|string|null $navigationGroup = 'Point of Sale';

    protected static ?string $navigationLabel = 'Tax & Service Fee';

    protected static ?string $modelLabel = 'Tax & Service Fee';

    protected static ?string $pluralModelLabel = 'Tax & Service Fee';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return StoreChargeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StoreChargeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreChargesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStoreCharges::route('/'),
            'create' => CreateStoreCharge::route('/create'),
            'view' => ViewStoreCharge::route('/{record}'),
            'edit' => EditStoreCharge::route('/{record}/edit'),
        ];
    }
}
