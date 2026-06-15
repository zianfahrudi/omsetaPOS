<?php

namespace App\Filament\Resources\CashierSessions;

use App\Filament\Resources\CashierSessions\Pages\CreateCashierSession;
use App\Filament\Resources\CashierSessions\Pages\ListCashierSessions;
use App\Filament\Resources\CashierSessions\Schemas\CashierSessionForm;
use App\Filament\Resources\CashierSessions\Tables\CashierSessionsTable;
use App\Models\CashierSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashierSessionResource extends Resource
{
    protected static ?string $model = CashierSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Sesi Kasir';

    protected static ?string $modelLabel = 'Sesi Kasir';

    protected static ?string $pluralModelLabel = 'Sesi Kasir';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CashierSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashierSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashierSessions::route('/'),
            'create' => CreateCashierSession::route('/create'),
        ];
    }
}
