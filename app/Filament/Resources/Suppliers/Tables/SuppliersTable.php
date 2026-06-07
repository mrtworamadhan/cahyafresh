<?php

namespace App\Filament\Resources\Suppliers\Tables;

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

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('phone')->label('No. Telepon')->searchable(),
                TextColumn::make('address')->label('Alamat')->limit(50),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('terima_deposit')
                    ->label('Deposit')
                    ->icon('heroicon-o-arrow-up-right')
                    ->color('danger')
                    ->form([
                        Select::make('wallet_id')
                            ->label('Masuk ke Dompet?')
                            ->options(\App\Models\Wallet::pluck('name', 'id'))
                            ->required(),
                        TextInput::make('amount')
                            ->label('Nominal Deposit')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)')),
                        TextInput::make('notes')
                            ->label('Catatan')
                            ->placeholder('Contoh: Titip uang untuk PO minggu depan'),
                    ])
                    ->action(function (array $data, $record) {
                        $cleanAmount = self::getSanitizedNumber($data['amount']);
                        // TARIK KATEGORI DEPOSIT SUPPLIER
                        $kategoriDeposit = \App\Models\FinanceCategory::where('code', 'ASSET_DEP_SUPPLIER')->first();

                        Ledger::create([
                            'business_id' => $record->business_id,
                            'wallet_id' => $data['wallet_id'],
                            'finance_category_id' => $kategoriDeposit?->id, // <--- TAMBAHKAN INI
                            'transaction_date' => now(),
                            'description' => 'Terima Deposit: ' . ($data['notes'] ?: 'Titip Saldo'),
                            'type' => 'out',
                            'amount' => $cleanAmount,
                            'contact_type' => \App\Models\Supplier::class,
                            'contact_id' => $record->id,
                        ]);

                        $record->increment('deposit_balance', $cleanAmount);

                        $wallet = \App\Models\Wallet::find($data['wallet_id']);
                        $wallet->increment('balance', $cleanAmount);

                        Notification::make()->title('Deposit Berhasil Dicatat!')->success()->send();
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
