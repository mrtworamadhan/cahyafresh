<?php

namespace App\Filament\Resources\Deliveries;

use App\Filament\Resources\Deliveries\Pages\ManageDeliveries;
use App\Models\Delivery;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    // protected static ?string $navigationGroup = 'Logistik & Operasional';

    protected static string|UnitEnum|null $navigationGroup = 'Logistik';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Surat Jalan';
    protected static ?string $pluralModelLabel = 'Surat Jalan Pengiriman';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengiriman')
                    ->schema([
                        TextInput::make('tracking_code')
                            ->label('Kode Resi / Tracking')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('courier_id')
                            ->label('Armada / Kurir')
                            ->relationship('courier', 'name', fn($query) => $query->where('business_id', Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id))
                            ->required(),

                        Select::make('status')
                            ->label('Status Pengiriman')
                            ->options([
                                'pending' => 'Menunggu Jadwal (Pending)',
                                'on_delivery' => 'Sedang Dikirim (On Delivery)',
                                'delivered' => 'Terkirim Sukses (Delivered)',
                                'failed' => 'Gagal Kirim (Failed)',
                            ])
                            ->required(),

                        Textarea::make('notes')
                            ->label('Catatan Ekspedisi')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Biaya Logistik')
                    ->schema([
                        TextInput::make('shipping_fee_billed')
                            ->label('Tagihan ke Konsumen (Rp)')
                            ->numeric()
                            ->disabled(), // Hanya read-only karena sudah dicatat oleh Kasir

                        TextInput::make('shipping_cost_actual')
                            ->label('Biaya Aktual / Beban Riil (Rp)')
                            ->numeric()
                            ->disabled(),
                    ])->columns(2),

                Section::make('Bukti Pengiriman (Proof of Delivery)')
                    ->schema([
                        TextInput::make('receiver_name')
                            ->label('Nama Penerima di Lokasi')
                            ->maxLength(255),

                        DateTimePicker::make('delivered_at')
                            ->label('Waktu Diterima'),

                        FileUpload::make('proof_photo_path')
                            ->label('Foto Bukti Kirim')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(5120) // 5MB
                            ->directory('proofs'),

                        // Tanda tangan sementara pakai Textarea, nanti di Tahap 4 kita ubah jadi kanvas coretan digital
                        Textarea::make('signature_data')
                            ->label('Data Tanda Tangan (Sistem)')
                            ->disabled(),
                    ])->columns(2)->visible(fn ($record) => $record !== null), // Hanya muncul kalau sedang edit data
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(function (Builder $query) {
                $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
                return $query->where('business_id', $businessId)->latest();
            })
            ->columns([
                TextColumn::make('tracking_code')
                    ->label('Kode Resi')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('order.order_number')
                    ->label('No. Nota')
                    ->searchable()
                    ->color('primary'),

                TextColumn::make('courier.name')
                    ->label('Kurir / Ekspedisi')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'on_delivery',
                        'success' => 'delivered',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'on_delivery' => 'Di Jalan',
                        'delivered' => 'Terkirim',
                        'failed' => 'Gagal',
                        default => $state,
                    }),

                TextColumn::make('shipping_cost_actual')
                    ->label('Beban Biaya')
                    ->money('IDR', true)
                    ->sortable(),

                IconColumn::make('is_paid_to_courier')
                    ->label('Lunas Vendor?')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('delivered_at')
                    ->label('Tgl Selesai')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu Jadwal',
                        'on_delivery' => 'Sedang Dikirim',
                        'delivered' => 'Terkirim Sukses',
                        'failed' => 'Gagal Kirim',
                    ]),
                SelectFilter::make('courier_id')
                    ->label('Filter Kurir')
                    ->relationship('courier', 'name'),
            ])
            ->recordActions([
                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning') 
                    ->form([
                        Select::make('status')
                            ->label('Ubah Status Pengiriman')
                            ->options([
                                'pending' => 'Menunggu Jadwal (Pending)',
                                'on_delivery' => 'Sedang Dikirim (On Delivery)',
                                'delivered' => 'Terkirim Sukses (Delivered)',
                                'failed' => 'Gagal Kirim (Failed)',
                            ])
                            ->default(fn ($record) => $record->status) 
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $updateData = ['status' => $data['status']];
                        
                        if ($data['status'] === 'delivered' && empty($record->delivered_at)) {
                            $updateData['delivered_at'] = now();
                        }

                        $record->update($updateData);

                        Notification::make()
                            ->title('Status pengiriman berhasil diperbarui!')
                            ->success()
                            ->send();
                    }),
                Action::make('pay_courier')
                    ->label('Release Ongkir')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => !$record->is_paid_to_courier && $record->shipping_cost_actual > 0)
                    ->form([
                        Select::make('wallet_id')
                            ->label('Pilih Sumber Dana (Dompet)')
                            ->options(fn() => \App\Models\Wallet::where('business_id', Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id)->where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $kategoriOngkir = \App\Models\FinanceCategory::where('code', 'OP_SHIPPING')->first();

                        \App\Models\Ledger::create([
                            'business_id' => $record->business_id,
                            'wallet_id' => $data['wallet_id'],
                            'finance_category_id' => $kategoriOngkir?->id,
                            'transaction_date' => now(),
                            'description' => "Pembayaran Ekspedisi/Kurir (" . ($record->courier?->name ?? 'Internal') . ") Nota: " . $record->order->order_number,
                            'type' => 'out', 
                            'amount' => $record->shipping_cost_actual,
                            'reference_type' => Delivery::class,
                            'reference_id' => $record->id,
                        ]);

                        $wallet = \App\Models\Wallet::find($data['wallet_id']);
                        if ($wallet) {
                            $wallet->decrement('balance', $record->shipping_cost_actual);
                        }

                        $record->update(['is_paid_to_courier' => true]);

                        Notification::make()
                            ->title('Pembayaran ke Kurir Berhasil Dirilis')
                            ->success()
                            ->send();
                    }),
                // EditAction::make(),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    
                    BulkAction::make('pay_courier_bulk')
                        ->label('Release Ongkir Massal')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Tindakan ini akan memotong saldo dompet Anda untuk melunasi semua ongkir Surat Jalan yang dipilih.')
                        ->form([
                            Select::make('wallet_id')
                                ->label('Pilih Sumber Dana (Dompet)')
                                ->options(fn() => \App\Models\Wallet::where('business_id', Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id)->where('is_active', true)->pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $totalPaid = 0;
                            $count = 0;

                            $kategoriOngkir = \App\Models\FinanceCategory::where('code', 'OP_SHIPPING')->first();

                            foreach ($records as $record) {
                                if (!$record->is_paid_to_courier && $record->shipping_cost_actual > 0) {
                                    
                                    \App\Models\Ledger::create([
                                        'business_id' => $record->business_id,
                                        'wallet_id' => $data['wallet_id'],
                                        'finance_category_id' => $kategoriOngkir?->id, 
                                        'transaction_date' => now(),
                                        'description' => "Pelunasan Ekspedisi (" . ($record->courier?->name ?? 'Internal') . ") Nota: " . $record->order->order_number,
                                        'type' => 'out',
                                        'amount' => $record->shipping_cost_actual,
                                        'reference_type' => Delivery::class, 
                                        'reference_id' => $record->id,
                                    ]);

                                    $record->update(['is_paid_to_courier' => true]);
                                    
                                    $totalPaid += $record->shipping_cost_actual;
                                    $count++;
                                }
                            }

                            if ($totalPaid > 0) {
                                $wallet = \App\Models\Wallet::find($data['wallet_id']);
                                if ($wallet) {
                                    $wallet->decrement('balance', $totalPaid);
                                }

                                Notification::make()
                                    ->title("Berhasil melunasi $count tagihan kurir (Total: Rp " . number_format($totalPaid, 0, ',', '.') . ")")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Tidak ada tagihan valid yang diproses.')
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDeliveries::route('/'),
        ];
    }
}
