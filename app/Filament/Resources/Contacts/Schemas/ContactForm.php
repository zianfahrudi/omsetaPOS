<?php

namespace App\Filament\Resources\Contacts\Schemas;

use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->default(fn () => Company::query()->value('id'))
                    ->required(),
                Select::make('type')
                    ->label('Jenis Kontak')
                    ->options([
                        'customer' => 'Pelanggan',
                        'supplier' => 'Supplier',
                        'employee' => 'Karyawan',
                        'other' => 'Lainnya',
                    ])
                    ->default('customer')
                    ->required(),
                TextInput::make('code')
                    ->label('Kode')
                    ->maxLength(50),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(40)
                    ->helperText('Lengkapi nomor telepon agar mudah dihubungi untuk tindak lanjut transaksi.'),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255)
                    ->helperText('Lengkapi alamat email agar mudah dihubungi untuk tindak lanjut transaksi.'),
                TextInput::make('tax_number')
                    ->label('NPWP')
                    ->maxLength(50),
                Textarea::make('address')
                    ->label('Alamat')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
