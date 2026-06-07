<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Wallet;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('No. Pesanan')->searchable(),
                TextColumn::make('order_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('customer.name')->label('Pelanggan')->searchable(),
                TextColumn::make('total_amount')->label('Total')->money('IDR')->sortable(),
                TextColumn::make('remaining_balance')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->color('danger') 
                    ->weight('bold'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'canceled' => 'danger',
                    }),
                TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                    }),
            ])
            ->defaultSort('order_date', 'desc')
            ->recordActions([
                EditAction::make(),
                Action::make('download_pdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger') // Warna merah khas PDF
                    ->url(fn ($record) => url('/invoice/' . $record->order_number . '/download'))
                    ->openUrlInNewTab(),
                Action::make('print_batch')
                    ->label('Cetak SJ & Inv')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn ($record) => route('invoice.print-batch', $record->order_number))
                    ->openUrlInNewTab(),
                Action::make('preview_batch')
                    ->label('Preview SJ & Inv')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn ($record) => route('invoice.preview-batch', $record->order_number))
                    ->openUrlInNewTab(),
                Action::make('ubah_status_pesanan')
                    ->label('Status')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->fillForm(fn (\App\Models\Order $record): array => [
                        'status' => $record->status,
                    ])
                    ->form([
                        Select::make('status')
                            ->label('Status Pesanan')
                            ->options([
                                'draft' => 'Draf / Baru Catat',
                                'processing' => 'Diproses / Disiapkan',
                                'completed' => 'Selesai (Kirim & Potong Stok)',
                                'canceled' => 'Dibatalkan',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, \App\Models\Order $record): void {
                        $record->update(['status' => $data['status']]);
                        
                        Notification::make()
                            ->title('Status pesanan berhasil diperbarui!')
                            ->success()
                            ->send();
                    }),

                Action::make('ubah_status_pembayaran')
                    ->label('Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (\App\Models\Order $record) => $record->remaining_balance > 0)
                    ->fillForm(fn (\App\Models\Order $record): array => [
                        'payment_status' => $record->payment_status,
                        'payment_amount' => $record->remaining_balance, 
                    ])
                    ->form([
                        Select::make('payment_status')
                            ->label('Ubah Status Menjadi')
                            ->options([
                                'partial' => 'Dibayar Sebagian (DP / Cicil)',
                                'paid' => 'Lunas',
                            ])
                            ->required(),
                            
                        Select::make('wallet_id')
                            ->label('Masuk ke Dompet Mana?')
                            ->options(Wallet::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                            
                        TextInput::make('payment_amount')
                            ->label('Nominal yang Diterima')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->formatStateUsing(function ($state) {
                                return $state ? str_replace('.', ',', (string)(float)$state) : null;
                            }),
                    ])
                    ->action(function (array $data, \App\Models\Order $record): void {
                        $amountPaid = self::getSanitizedNumber($data['payment_amount']);

                        if ($amountPaid <= 0 || $amountPaid > $record->remaining_balance) {
                            Notification::make()->title('Nominal pembayaran tidak valid!')->danger()->send();
                            return;
                        }

                        $kategoriPenjualan = \App\Models\FinanceCategory::where('code', 'INC_AR')->first();
                        $kategoriOngkir = \App\Models\FinanceCategory::where('code', 'INC_SHIPPING')->first();

                        $ongkirSudahDibayar = \App\Models\Ledger::where('reference_type', \App\Models\Order::class)
                            ->where('reference_id', $record->id)
                            ->where('finance_category_id', $kategoriOngkir?->id)
                            ->sum('amount');

                        $sisaTagihanOngkir = max(0, (float)$record->shipping_fee_billed - $ongkirSudahDibayar);
                        
                        $ongkirDibayar = min($amountPaid, $sisaTagihanOngkir);
                        $barangDibayar = $amountPaid - $ongkirDibayar;

                        if ($barangDibayar > 0) {
                            \App\Models\Ledger::create([
                                'business_id' => $record->business_id,
                                'wallet_id' => $data['wallet_id'],
                                'finance_category_id' => $kategoriPenjualan?->id,
                                'transaction_date' => now(),
                                'description' => 'Pelunasan Piutang Barang Nota: ' . $record->order_number,
                                'type' => 'in',
                                'amount' => $barangDibayar,
                                'contact_type' => \App\Models\Customer::class,
                                'contact_id' => $record->customer_id,
                                'reference_type' => \App\Models\Order::class,
                                'reference_id' => $record->id,
                            ]);
                        }

                        if ($ongkirDibayar > 0) {
                            \App\Models\Ledger::create([
                                'business_id' => $record->business_id,
                                'wallet_id' => $data['wallet_id'],
                                'finance_category_id' => $kategoriOngkir?->id,
                                'transaction_date' => now(),
                                'description' => 'Pelunasan Ongkir Nota: ' . $record->order_number,
                                'type' => 'in',
                                'amount' => $ongkirDibayar,
                                'contact_type' => \App\Models\Customer::class,
                                'contact_id' => $record->customer_id,
                                'reference_type' => \App\Models\Order::class,
                                'reference_id' => $record->id,
                            ]);
                        }

                        $wallet = Wallet::find($data['wallet_id']);
                        if ($wallet) {
                            $wallet->increment('balance', $amountPaid);
                        }

                        $newRemaining = $record->remaining_balance - $amountPaid;
                        $finalStatus = $newRemaining <= 0 ? 'paid' : $data['payment_status'];

                        $record->update(['payment_status' => $finalStatus]);
                        
                        Notification::make()->title('Pembayaran berhasil diterima!')->success()->send();
                    })
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function getSanitizedNumber($value): float
    {
        if (empty($value)) {
            return 0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $cleanString = str_replace(['.', ','], ['', '.'], (string) $value);
        return (float) $cleanString;
    }
}
