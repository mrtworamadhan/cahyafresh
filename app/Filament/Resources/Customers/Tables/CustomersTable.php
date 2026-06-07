<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Ledger;
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

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable(),
                TextColumn::make('referral_code')
                    ->label('Kode Referral')
                    ->badge() 
                    ->color('success')
                    ->searchable(),
                TextColumn::make('referrer.name')
                    ->label('Upline')
                    ->sortable(),
                TextColumn::make('commission_balance')
                    ->label('Saldo Komisi')
                    ->money('IDR')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'secondary'),
            ])
            ->filters([
                // Nanti kita bisa tambah filter di sini
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('terima_deposit')
                    ->label('Terima Deposit')
                    ->icon('heroicon-o-arrow-down-left')
                    ->color('success')
                    ->form([
                        Select::make('wallet_id')
                            ->label('Masuk ke Dompet?')
                            ->options(\App\Models\Wallet::pluck('name', 'id'))
                            ->required(),
                        TextInput::make('amount')
                            ->label('Nominal Deposit')
                            ->prefix('Rp')
                            ->required()
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)')), 
                        TextInput::make('notes')
                            ->label('Catatan')
                            ->placeholder('Contoh: Titip uang untuk PO minggu depan'),
                    ])
                    ->action(function (array $data, $record) {
                        $rawAmount = (string) $data['amount'];
                        $cleanString = str_replace(['.', ','], ['', '.'], $rawAmount);
                        
                        // 3. Jadikan angka float murni (Contoh: 189800.00)
                        $cleanAmount = (float) $cleanString; 
                            Ledger::create([
                            'business_id' => $record->business_id,
                            'wallet_id' => $data['wallet_id'],
                            'finance_category_id' => \App\Models\FinanceCategory::where('code', 'INC_DEPOSIT')->first()?->id,
                            'transaction_date' => now(),
                            'description' => 'Terima Deposit: ' . ($data['notes'] ?: 'Titipan pelanggan'),
                            'type' => 'in', 
                            'amount' => $cleanAmount, // Masukkan angka yang udah bersih!
                            'contact_type' => \App\Models\Customer::class,
                            'contact_id' => $record->id,
                        ]);

                        // Tambah Saldo Dompet Fisik
                        $wallet = \App\Models\Wallet::find($data['wallet_id']);
                        if ($wallet) {
                            $wallet->balance += $cleanAmount;
                            $wallet->save();
                        }

                        // Tambah Saldo Deposit Customer
                        $record->balance += $cleanAmount;
                        $record->save();

                        Notification::make()->title('Deposit Berhasil Diterima!')->success()->send();
                    }),

                Action::make('cairkan_komisi')
                    ->label('Cairkan Komisi')
                    ->icon('heroicon-o-gift')
                    ->color('warning') 
                    ->visible(fn (\App\Models\Customer $record) => $record->commission_balance > 0)
                    ->form(fn (\App\Models\Customer $record) => [
                        Select::make('wallet_id')
                            ->label('Tarik Dana Dari Dompet?')
                            ->options(\App\Models\Wallet::where('business_id', $record->business_id)->pluck('name', 'id'))
                            ->required(),
                        
                        TextInput::make('amount')
                            ->label('Nominal Pencairan')
                            ->prefix('Rp')
                            ->default($record->commission_balance) // Default isi otomatis dengan seluruh saldo
                            ->required()
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)')) 
                            ->formatStateUsing(function ($state) {
                                return $state ? str_replace('.', ',', (string)(float)$state) : null;
                            }),
                            
                        TextInput::make('notes')
                            ->label('Catatan')
                            ->default('Pencairan komisi agen/referral'),
                    ])
                    ->action(function (array $data, $record) {
                        $rawAmount = (string) $data['amount'];
                        $cleanString = str_replace(['.', ','], ['', '.'], $rawAmount);
                        $cleanAmount = (float) $cleanString; // Jadi angka murni

                        if ($cleanAmount > $record->commission_balance) {
                            Notification::make()->title('Gagal! Nominal melebihi saldo komisi.')->danger()->send();
                            return;
                        }

                        $kategoriKomisi = \App\Models\FinanceCategory::where('code', 'OP_COMMISSION')->first();

                        Ledger::create([
                            'business_id' => $record->business_id,
                            'wallet_id' => $data['wallet_id'],
                            'finance_category_id' => $kategoriKomisi?->id,
                            'transaction_date' => now(),
                            'description' => 'Pencairan Komisi: ' . ($data['notes'] ?: 'Referral'),
                            'type' => 'out', 
                            'amount' => $cleanAmount,
                            'contact_type' => \App\Models\Customer::class,
                            'contact_id' => $record->id,
                        ]);
                        
                        // Potong Saldo Komisi Agen
                        $record->commission_balance -= $cleanAmount;
                        $record->save();

                        // Potong Saldo Fisik di Dompet Kasir
                        $wallet = \App\Models\Wallet::find($data['wallet_id']);
                        if ($wallet) {
                            $wallet->balance -= $cleanAmount;
                            $wallet->save();
                        }

                        Notification::make()->title('Komisi Berhasil Dicairkan!')->success()->send();
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
