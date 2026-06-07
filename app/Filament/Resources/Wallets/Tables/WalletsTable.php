<?php

namespace App\Filament\Resources\Wallets\Tables;

use App\Models\Ledger;
use App\Models\Wallet;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Support\RawJs;

class WalletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Dompet')
                    ->searchable(),
                    
                TextColumn::make('balance')
                    ->label('Total Saldo')
                    ->money('IDR')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
                    
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                
                Action::make('suntik_modal')
                    ->label('Suntik Modal')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->label('Nominal Modal Masuk')
                            // ->numeric() // <--- BAGIAN INI KITA HAPUS
                            ->prefix('Rp')
                            ->required()
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)')),
                        TextInput::make('notes')
                            ->label('Keterangan')
                            ->default('Suntik Modal Tambahan')
                            ->required(),
                    ])
                    ->action(function (Wallet $record, array $data) {
                        // Sistem membersihkan titik/koma di sini
                        $cleanAmount = (float) str_replace(['.', ','], ['', '.'], $data['amount']);

                        $kategoriModal = \App\Models\FinanceCategory::where('code', 'EQ_MODAL')->first();

                        Ledger::create([
                            'business_id' => $record->business_id,
                            'wallet_id' => $record->id,
                            'finance_category_id' => $kategoriModal?->id, // <--- TAMBAHKAN INI
                            'transaction_date' => now(),
                            'description' => 'Modal Eksekutif: ' . $data['notes'],
                            'type' => 'in', 
                            'amount' => $cleanAmount,
                        ]);

                        $record->increment('balance', $cleanAmount);

                        Notification::make()->title('Modal Berhasil Ditambahkan!')->success()->send();
                    }),

                Action::make('tarik_prive')
                    ->label('Tarik Dana')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('danger')
                    ->form([
                        TextInput::make('amount')
                            ->label('Nominal Penarikan')
                            // ->numeric() // <--- BAGIAN INI JUGA KITA HAPUS
                            ->prefix('Rp')
                            ->required()
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)')),
                        TextInput::make('notes')
                            ->label('Keterangan')
                            ->default('Tarik Keuntungan / Prive')
                            ->required(),
                    ])
                    ->action(function (Wallet $record, array $data) {
                        $cleanAmount = (float) str_replace(['.', ','], ['', '.'], $data['amount']);

                        if ($cleanAmount > $record->balance) {
                            Notification::make()
                                ->title('Penarikan Gagal!')
                                ->body('Saldo di ' . $record->name . ' tidak mencukupi untuk penarikan ini.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $kategoriPrive = \App\Models\FinanceCategory::where('code', 'EQ_PRIVE')->first();

                        Ledger::create([
                            'business_id' => $record->business_id,
                            'wallet_id' => $record->id,
                            'finance_category_id' => $kategoriPrive?->id, // <--- TAMBAHKAN INI
                            'transaction_date' => now(),
                            'description' => 'Prive Eksekutif: ' . $data['notes'],
                            'type' => 'out', 
                            'amount' => $cleanAmount,
                        ]);

                        $record->decrement('balance', $cleanAmount);

                        Notification::make()->title('Penarikan Dana Berhasil!')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
