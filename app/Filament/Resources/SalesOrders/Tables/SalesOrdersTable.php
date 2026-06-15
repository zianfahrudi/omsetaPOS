<?php

namespace App\Filament\Resources\SalesOrders\Tables;

use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

class SalesOrdersTable
{
    private const STATUS = [
        'draft' => 'Draft',
        'confirmed' => 'Dikonfirmasi',
        'invoiced' => 'Difakturkan',
        'cancelled' => 'Dibatalkan',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('customer.name')->label('Pelanggan')->searchable(),
                TextColumn::make('grand_total')->label('Total')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'invoiced' => 'success',
                        'cancelled' => 'danger',
                        'confirmed' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('invoice.number')->label('Faktur')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(self::STATUS),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('convert')
                    ->label('Konversi ke Faktur')
                    ->icon('heroicon-o-document-text')
                    ->requiresConfirmation()
                    ->visible(fn (SalesOrder $record): bool => ! $record->isInvoiced())
                    ->action(function (SalesOrder $record): void {
                        try {
                            app(SalesOrderService::class)->convertToInvoice($record, auth()->id());
                            Notification::make()->title('Pesanan dikonversi menjadi faktur')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
