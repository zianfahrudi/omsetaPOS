<?php

namespace App\Filament\Resources\Journals;

use App\Filament\Resources\Journals\Pages\CreateJournal;
use App\Filament\Resources\Journals\Pages\ListJournals;
use App\Filament\Resources\Journals\Pages\ViewJournal;
use App\Filament\Resources\Journals\Schemas\JournalForm;
use App\Filament\Resources\Journals\Schemas\JournalInfolist;
use App\Filament\Resources\Journals\Tables\JournalsTable;
use App\Models\Journal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Akuntansi';

    protected static ?string $navigationLabel = 'Jurnal';

    protected static ?string $modelLabel = 'Jurnal';

    protected static ?string $pluralModelLabel = 'Jurnal';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return JournalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JournalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalsTable::configure($table);
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
            'index' => ListJournals::route('/'),
            'create' => CreateJournal::route('/create'),
            'view' => ViewJournal::route('/{record}'),
        ];
    }
}
