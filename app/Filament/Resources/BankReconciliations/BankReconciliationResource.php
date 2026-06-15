<?php

namespace App\Filament\Resources\BankReconciliations;

use App\Filament\Resources\BankReconciliations\Pages\CreateBankReconciliation;
use App\Filament\Resources\BankReconciliations\Pages\ListBankReconciliations;
use App\Filament\Resources\BankReconciliations\Schemas\BankReconciliationForm;
use App\Filament\Resources\BankReconciliations\Tables\BankReconciliationsTable;
use App\Models\BankReconciliation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankReconciliationResource extends Resource
{
    protected static ?string $model = BankReconciliation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Kas & Bank';

    protected static ?string $navigationLabel = 'Rekonsiliasi Bank';

    protected static ?string $modelLabel = 'Rekonsiliasi Bank';

    protected static ?string $pluralModelLabel = 'Rekonsiliasi Bank';

    public static function form(Schema $schema): Schema
    {
        return BankReconciliationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankReconciliationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankReconciliations::route('/'),
            'create' => CreateBankReconciliation::route('/create'),
        ];
    }
}
