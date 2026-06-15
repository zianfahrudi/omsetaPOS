<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

class PurchaseRequestsTable
{
    private const STATUS = [
        'draft' => 'Draft',
        'approved' => 'Disetujui',
        'ordered' => 'Jadi Pesanan',
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
                TextColumn::make('grand_total')->label('Estimasi')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'ordered' => 'success',
                        'cancelled' => 'danger',
                        'approved' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('order.number')->label('Pesanan')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(self::STATUS),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('convert')
                    ->label('Konversi ke Pesanan')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseRequest $record): bool => ! $record->isOrdered())
                    ->action(function (PurchaseRequest $record): void {
                        try {
                            app(PurchaseRequestService::class)->convertToOrder($record, auth()->id());
                            Notification::make()->title('Permintaan dikonversi menjadi pesanan')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
