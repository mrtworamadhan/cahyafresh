<?php

namespace App\Filament\Resources\Couriers\RelationManagers;

use App\Models\Delivery;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;

class DeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveries';
    protected static ?string $title = 'Riwayat Surat Jalan & Ongkir';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tracking_code')
            ->columns([
                TextColumn::make('tracking_code')->label('Resi')->weight('bold'),
                TextColumn::make('order.order_number')->label('No. Nota'),
                TextColumn::make('shipping_cost_actual')->label('Beban Ongkir')->money('IDR', true)->color('danger'),
                IconColumn::make('is_paid_to_courier')->label('Status Lunas?')->boolean(),
                TextColumn::make('delivered_at')->label('Selesai')->dateTime('d M Y')->placeholder('Belum'),
            ])
            ->actions([
                Action::make('pay_courier')
                    ->label('Bayar Ongkir')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => !$record->is_paid_to_courier && $record->shipping_cost_actual > 0)
                    ->form([
                        Select::make('wallet_id')
                            ->label('Sumber Dana (Dompet)')
                            ->options(fn() => \App\Models\Wallet::where('business_id', Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id)->where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data, $livewire) {
                        $kategoriOngkir = \App\Models\FinanceCategory::where('code', 'OP_SHIPPING')->first();
                        
                        \App\Models\Ledger::create([
                            'business_id' => $record->business_id,
                            'wallet_id' => $data['wallet_id'],
                            'finance_category_id' => $kategoriOngkir?->id,
                            'transaction_date' => now(),
                            'description' => "Pembayaran Ekspedisi (" . ($record->courier?->name ?? 'Internal') . ") Nota: " . $record->order->order_number,
                            'type' => 'out',
                            'amount' => $record->shipping_cost_actual,
                            'reference_type' => Delivery::class,
                            'reference_id' => $record->id,
                        ]);

                        $wallet = \App\Models\Wallet::find($data['wallet_id']);
                        if ($wallet) $wallet->decrement('balance', $record->shipping_cost_actual);

                        $record->update(['is_paid_to_courier' => true]);

                        Notification::make()->title('Pembayaran Berhasil')->success()->send();
                        $livewire->dispatch('refreshCourierStats'); // Refresh widget di atas secara otomatis
                    }),
            ]);
    }
}