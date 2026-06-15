<?php

namespace App\Filament\Resources\Giros\Tables;

use App\Models\Account;
use App\Models\Giro;
use App\Services\GiroService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

class GirosTable
{
    private const STATUS = [
        'received' => 'Diterima',
        'deposited' => 'Disetor',
        'cleared' => 'Cair',
        'rejected' => 'Ditolak',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('customer.name')->label('Pelanggan')->searchable(),
                TextColumn::make('giro_number')->label('No. Giro')->searchable(),
                TextColumn::make('due_date')->label('Jatuh Tempo')->date(),
                TextColumn::make('amount')->label('Nominal')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'cleared' => 'success',
                        'rejected' => 'danger',
                        'deposited' => 'warning',
                        default => 'info',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(self::STATUS),
            ])
            ->recordActions([
                Action::make('deposit')
                    ->label('Setor')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (Giro $r): bool => $r->status === 'received')
                    ->requiresConfirmation()
                    ->action(fn (Giro $r) => self::run(fn () => app(GiroService::class)->deposit($r))),
                Action::make('clear')
                    ->label('Cairkan')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (Giro $r): bool => $r->isOpen())
                    ->schema([
                        Select::make('bank_account_id')
                            ->label('Akun Bank')
                            ->options(fn (Giro $r) => Account::query()
                                ->where('company_id', $r->company_id)
                                ->whereIn('subtype', ['bank', 'cash'])
                                ->pluck('name', 'id'))
                            ->required(),
                        DatePicker::make('date')->label('Tanggal')->default(now())->required(),
                    ])
                    ->action(fn (Giro $r, array $data) => self::run(fn () => app(GiroService::class)->clear($r, (int) $data['bank_account_id'], $data['date']))),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Giro $r): bool => $r->isOpen())
                    ->requiresConfirmation()
                    ->action(fn (Giro $r) => self::run(fn () => app(GiroService::class)->reject($r))),
            ]);
    }

    private static function run(callable $fn): void
    {
        try {
            $fn();
            Notification::make()->title('Berhasil')->success()->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
        }
    }
}
