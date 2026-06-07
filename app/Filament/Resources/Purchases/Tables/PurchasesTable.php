<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Models\Ledger;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Wallet;
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

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('No. Nota')->searchable(),
                TextColumn::make('purchase_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('total_amount')->label('Total Belanja')->money('IDR')->sortable(),
                TextColumn::make('remaining_balance')
                    ->label('Sisa Tagihan / Hutang')
                    ->money('IDR')
                    ->color('danger')
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'partial' => 'Dibayar Sebagian',
                        'unpaid' => 'Hutang',
                    }),
                
            ])
            ->defaultSort('purchase_date', 'desc')
            ->recordActions([
                EditAction::make(),
                Action::make('terima_stok')
                    ->label('Terima Stok')
                    ->icon('heroicon-o-cube')
                    ->color('success')
                    ->requiresConfirmation() 
                    ->modalHeading('Konfirmasi Terima Barang')
                    ->modalDescription('Apakah barang fisik sudah sampai di gudang? Setelah klik OK, stok produk akan bertambah dan tidak bisa diulang.')
                    ->visible(fn (\App\Models\Purchase $record) => !$record->is_stock_received) 
                    ->action(function (\App\Models\Purchase $record) {
                        
                        $record->update([
                            'is_stock_received' => true,
                        ]);
                        
                        Notification::make()
                            ->title('Stok diterima! Harga modal telah dirata-ratakan otomatis dan dicatat di riwayat.')
                            ->success()
                            ->send();
                    }),

                Action::make('bayar_nota')
                    ->label('Bayar Tagihan')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (\App\Models\Purchase $record) => $record->remaining_balance > 0)
                    ->fillForm(fn (\App\Models\Purchase $record): array => [
                        'status' => $record->status,
                        'payment_amount' => $record->remaining_balance, 
                    ])
                    ->form([
                        Select::make('status')
                            ->label('Ubah Status Menjadi')
                            ->options([
                                'partial' => 'Dibayar Sebagian (Cicil)',
                                'paid' => 'Lunas',
                            ])
                            ->required(),
                            
                        Select::make('wallet_id')
                            ->label('Ambil Uang dari Dompet Mana?')
                            ->options(Wallet::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                            
                        TextInput::make('payment_amount')
                            ->label('Nominal yang Dibayarkan')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->formatStateUsing(function ($state) {
                                return $state ? str_replace('.', ',', (string)(float)$state) : null;
                            }),
                    ])
                    ->action(function (array $data, \App\Models\Purchase $record): void {
                        $amountPaid = self::getSanitizedNumber($data['payment_amount']);

                        if ($amountPaid <= 0) {
                            Notification::make()
                                ->title('Nominal pembayaran tidak valid!')
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($amountPaid > $record->remaining_balance) {
                            Notification::make()
                                ->title('Nominal melebihi sisa tagihan/hutang!')
                                ->danger()
                                ->send();
                            return;
                        }

                        $wallet = Wallet::find($data['wallet_id']);
                        
                        if ($wallet && $wallet->balance < $amountPaid) {
                            Notification::make()
                                ->title('Saldo Dompet tidak mencukupi!')
                                ->danger()
                                ->send();
                            return;
                        }
                        // TARIK KATEGORI BAYAR HUTANG SUPPLIER
                        $kategoriHutang = \App\Models\FinanceCategory::where('code', 'LIA_AP')->first();

                        Ledger::create([
                            'business_id' => $record->business_id,
                            'wallet_id' => $data['wallet_id'],
                            'finance_category_id' => $kategoriHutang?->id, // <--- TAMBAHKAN INI
                            'transaction_date' => now(),
                            'description' => 'Pembayaran Tagihan Nota: ' . $record->invoice_number,
                            'type' => 'out', // UANG KELUAR
                            'amount' => $amountPaid,
                            'contact_type' => Supplier::class,
                            'contact_id' => $record->supplier_id,
                            'reference_type' => \App\Models\Purchase::class,
                            'reference_id' => $record->id,
                        ]);

                        if ($wallet) {
                            $wallet->balance -= $amountPaid; 
                            $wallet->save();
                        }

                        $newRemaining = $record->remaining_balance - $amountPaid;
                        $finalStatus = $newRemaining <= 0 ? 'paid' : $data['status'];

                        $record->update(['status' => $finalStatus]);
                        
                        Notification::make()
                            ->title('Pembayaran ke Supplier berhasil dan saldo dompet telah dipotong!')
                            ->success()
                            ->send();
                    }),
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
