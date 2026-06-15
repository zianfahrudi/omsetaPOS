<?php

namespace App\Filament\Resources\SalesInvoices\Tables;

use App\Models\SalesInvoice;
use App\Services\SalesInvoicePaymentService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use InvalidArgumentException;

class SalesInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('customer.name')->label('Pelanggan')->searchable(),
                TextColumn::make('grand_total')->label('Total')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('outstanding_amount')->label('Sisa Piutang')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (SalesInvoice $record): string => $record->paymentStatus())
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lunas' => 'Lunas',
                        'sebagian' => 'Sebagian',
                        default => 'Belum Lunas',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'lunas' => 'success',
                        'sebagian' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('receive')
                    ->label('Terima Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (SalesInvoice $record): bool => (float) $record->outstanding_amount > 0)
                    ->schema([
                        TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->required()
                            ->default(fn (SalesInvoice $record): float => (float) $record->outstanding_amount)
                            ->maxValue(fn (SalesInvoice $record): float => (float) $record->outstanding_amount),
                        Select::make('method')->label('Metode')->options(['cash' => 'Kas', 'bank' => 'Bank'])->default('cash')->required(),
                        DatePicker::make('date')->label('Tanggal')->default(now())->required(),
                    ])
                    ->action(function (SalesInvoice $record, array $data): void {
                        try {
                            app(SalesInvoicePaymentService::class)->pay(
                                invoice: $record,
                                amount: (float) $data['amount'],
                                method: $data['method'],
                                date: $data['date'],
                                createdBy: auth()->id(),
                            );
                            Notification::make()->title('Pembayaran tercatat')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
