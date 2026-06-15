<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

class PurchaseOrdersTable
{
    private const STATUS = [
        'draft' => 'Draft',
        'confirmed' => 'Dikonfirmasi',
        'received' => 'Difakturkan',
        'cancelled' => 'Dibatalkan',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('grand_total')->label('Total')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'success',
                        'cancelled' => 'danger',
                        'confirmed' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('purchase.number')->label('Faktur')->placeholder('-'),
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
                    ->visible(fn (PurchaseOrder $record): bool => ! $record->isReceived())
                    ->action(function (PurchaseOrder $record): void {
                        try {
                            app(PurchaseOrderService::class)->convertToPurchase($record, auth()->id());
                            Notification::make()->title('Pesanan dikonversi menjadi faktur')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
