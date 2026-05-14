<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Store;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state)),
                Select::make('role')
                    ->options([
                        'cashier' => 'Cashier',
                        'admin' => 'Admin',
                        'superuser' => 'Superuser',
                    ])
                    ->live()
                    ->required()
                    ->default('cashier'),
                Select::make('store_ids')
                    ->label('Toko terdaftar')
                    ->helperText('Cashier dan admin hanya bisa akses toko yang dipilih di sini.')
                    ->options(function () {
                        $user = Auth::user();

                        if ($user?->isSuperuser()) {
                            return Store::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id');
                        }

                        return $user?->stores()->where('stores.is_active', true)->orderBy('name')->pluck('stores.name', 'stores.id') ?? [];
                    })
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => in_array($get('role'), ['cashier', 'admin'], true))
                    ->visible(fn (Get $get): bool => in_array($get('role'), ['cashier', 'admin'], true))
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->tel(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
