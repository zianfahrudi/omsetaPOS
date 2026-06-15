<?php

namespace App\Filament\Resources\Giros;

use App\Filament\Resources\Giros\Pages\CreateGiro;
use App\Filament\Resources\Giros\Pages\ListGiros;
use App\Filament\Resources\Giros\Schemas\GiroForm;
use App\Filament\Resources\Giros\Tables\GirosTable;
use App\Models\Giro;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GiroResource extends Resource
{
    protected static ?string $model = Giro::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Kas & Bank';

    protected static ?string $navigationLabel = 'Giro Masuk';

    protected static ?string $modelLabel = 'Giro';

    protected static ?string $pluralModelLabel = 'Giro Masuk';

    public static function form(Schema $schema): Schema
    {
        return GiroForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GirosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGiros::route('/'),
            'create' => CreateGiro::route('/create'),
        ];
    }
}
