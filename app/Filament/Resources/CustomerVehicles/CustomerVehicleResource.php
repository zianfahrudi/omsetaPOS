<?php

namespace App\Filament\Resources\CustomerVehicles;

use App\Filament\Resources\CustomerVehicles\Pages\CreateCustomerVehicle;
use App\Filament\Resources\CustomerVehicles\Pages\EditCustomerVehicle;
use App\Filament\Resources\CustomerVehicles\Pages\ListCustomerVehicles;
use App\Filament\Resources\CustomerVehicles\Pages\ViewCustomerVehicle;
use App\Filament\Resources\CustomerVehicles\Schemas\CustomerVehicleForm;
use App\Filament\Resources\CustomerVehicles\Schemas\CustomerVehicleInfolist;
use App\Filament\Resources\CustomerVehicles\Tables\CustomerVehiclesTable;
use App\Models\CustomerVehicle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerVehicleResource extends Resource
{
    protected static ?string $model = CustomerVehicle::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Kendaraan';

    protected static ?string $modelLabel = 'Kendaraan';

    protected static ?string $pluralModelLabel = 'Kendaraan';

    protected static \UnitEnum|string|null $navigationGroup = 'Management';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['store', 'customer', 'latestServiceSale.items']);
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerVehicleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerVehicleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerVehiclesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerVehicles::route('/'),
            'create' => CreateCustomerVehicle::route('/create'),
            'view' => ViewCustomerVehicle::route('/{record}'),
            'edit' => EditCustomerVehicle::route('/{record}/edit'),
        ];
    }
}
