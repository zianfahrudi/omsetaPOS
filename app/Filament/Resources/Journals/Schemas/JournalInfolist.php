<?php

namespace App\Filament\Resources\Journals\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JournalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Jurnal')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('number')->label('Nomor'),
                        TextEntry::make('date')->label('Tanggal')->date(),
                        TextEntry::make('type')->label('Tipe'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('reference')->label('Referensi')->placeholder('-'),
                        TextEntry::make('source_type')->label('Sumber')->placeholder('-'),
                        TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Baris Jurnal')
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->hiddenLabel()
                            ->columns(12)
                            ->schema([
                                TextEntry::make('account.code')->label('Kode')->columnSpan(2),
                                TextEntry::make('account.name')->label('Akun')->columnSpan(5),
                                TextEntry::make('debit')->label('Debit')->numeric(decimalPlaces: 2)->columnSpan(2),
                                TextEntry::make('credit')->label('Kredit')->numeric(decimalPlaces: 2)->columnSpan(2),
                                TextEntry::make('memo')->label('Catatan')->placeholder('-')->columnSpan(1),
                            ]),
                        TextEntry::make('total_debit')->label('Total Debit')->numeric(decimalPlaces: 2),
                        TextEntry::make('total_credit')->label('Total Kredit')->numeric(decimalPlaces: 2),
                    ]),
            ]);
    }
}
