<?php

namespace App\Filament\Resources\SalesReturns;

use App\Filament\Resources\SalesReturns\Pages\CreateSalesReturn;
use App\Filament\Resources\SalesReturns\Pages\ListSalesReturns;
use App\Filament\Resources\SalesReturns\Pages\ViewSalesReturn;
use App\Filament\Resources\SalesReturns\Schemas\SalesReturnForm;
use App\Filament\Resources\SalesReturns\Schemas\SalesReturnInfolist;
use App\Filament\Resources\SalesReturns\Tables\SalesReturnsTable;
use App\Models\SalesReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SalesReturnResource extends Resource
{
    protected static ?string $model = SalesReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Retur Penjualan';

    protected static ?string $modelLabel = 'Retur Penjualan';

    protected static ?string $pluralModelLabel = 'Retur Penjualan';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SalesReturnForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalesReturnInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesReturnsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesReturns::route('/'),
            'create' => CreateSalesReturn::route('/create'),
            'view' => ViewSalesReturn::route('/{record}'),
        ];
    }
}
