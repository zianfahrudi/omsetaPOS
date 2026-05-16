<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('store.name')
                    ->label('Store'),
                TextEntry::make('cashier.name')
                    ->label('Cashier')
                    ->placeholder('-'),
                TextEntry::make('customer.name')
                    ->label('Customer CRM')
                    ->placeholder('-'),
                TextEntry::make('number'),
                TextEntry::make('customer_name')
                    ->placeholder('-'),
                TextEntry::make('customer_phone')
                    ->placeholder('-'),
                TextEntry::make('vehicle_plate_number')
                    ->label('Nomor plat')
                    ->placeholder('-'),
                TextEntry::make('vehicle_mileage')
                    ->label('Kilometer')
                    ->numeric()
                    ->suffix(' km')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('payment_method'),
                TextEntry::make('payment_status')
                    ->label('Status pembayaran')
                    ->state(fn ($record): string => (float) $record->debt_amount > 0 ? 'Belum lunas' : 'Lunas')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Belum lunas' ? 'warning' : 'success'),
                TextEntry::make('is_debt')
                    ->label('Dibuat sebagai hutang')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak'),
                ImageEntry::make('payment_proof')
                    ->label('Bukti transfer / QRIS')
                    ->disk('public')
                    ->imageHeight(320)
                    ->visible(fn ($record): bool => $record->payment_method === 'qris' && filled($record->payment_proof)),
                TextEntry::make('payment_proof')
                    ->label('File bukti pembayaran')
                    ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '-')
                    ->url(fn ($record): ?string => $record->payment_proof ? asset('storage/'.$record->payment_proof) : null, shouldOpenInNewTab: true)
                    ->visible(fn ($record): bool => $record->payment_method === 'qris'),
                TextEntry::make('subtotal')
                    ->numeric(),
                TextEntry::make('discount_code')
                    ->label('Kode diskon')
                    ->placeholder('-'),
                TextEntry::make('discount_type')
                    ->label('Tipe diskon')
                    ->placeholder('-'),
                TextEntry::make('discount_value')
                    ->label('Nilai diskon')
                    ->numeric(),
                TextEntry::make('discount_total')
                    ->numeric(),
                TextEntry::make('tax_percentage')
                    ->label('Tax global (%)')
                    ->numeric(),
                TextEntry::make('tax_total')
                    ->label('Tax produk')
                    ->state(fn ($record): float => $record->productTaxTotal())
                    ->numeric(),
                TextEntry::make('service_fee_percentage')
                    ->label('Service fee global (%)')
                    ->numeric(),
                TextEntry::make('service_fee_total')
                    ->label('Service fee produk')
                    ->state(fn ($record): float => $record->productServiceFeeTotal())
                    ->numeric(),
                TextEntry::make('grand_total')
                    ->numeric(),
                TextEntry::make('paid_amount')
                    ->numeric(),
                TextEntry::make('change_amount')
                    ->numeric(),
                TextEntry::make('debt_amount')
                    ->label('Nominal hutang')
                    ->numeric(),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
