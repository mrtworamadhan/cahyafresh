<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pesanan')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Nomor Pesanan / Nota')
                            ->required()
                            ->default(function () {
                                $businessId = Filament::getTenant()->id;
                                $lastOrder = Order::where('business_id', $businessId)->latest('id')->first();

                                if (! $lastOrder || empty($lastOrder->order_number)) {
                                    return 'ORD-0001';
                                }

                                $lastNumber = (int) substr($lastOrder->order_number, 4);
                                return 'ORD-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                            })
                            ->readOnly(),
                            
                        DatePicker::make('order_date')
                            ->label('Tanggal Pesanan')
                            ->default(now())
                            ->required(),

                        Select::make('customer_id')
                            ->label('Customer / Pelanggan')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('phone'),
                            ]),

                        Select::make('po_batch_id')
                            ->label('Kelompok PO (Opsional)')
                            ->relationship('poBatch', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->label('Status Pesanan')
                            ->options([
                                'draft' => 'Draf / Baru Catat',
                                'processing' => 'Diproses / Disiapkan',
                                'completed' => 'Selesai (Stok Terpotong)',
                                'canceled' => 'Dibatalkan',
                            ])
                            ->default('draft')
                            ->required(),
                            
                        Select::make('payment_status')
                            ->label('Status Pembayaran')
                            ->options([
                                'unpaid' => 'Belum Dibayar (Piutang)',
                                'partial' => 'Dibayar Sebagian',
                                'paid' => 'Lunas',
                            ])
                            ->default('unpaid')
                            ->required(),
                    ])->columns(2),

                Section::make('Daftar Barang (Item Pesanan)')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('orderItems')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Pilih Produk')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(3)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $set('product_unit_id', null);
                                        
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $priceStr = str_replace('.', ',', (string)(float)$product->selling_price);
                                                $set('unit_price', $priceStr);
                                                self::calculateItemSubtotal($set, $get);
                                            }
                                        }
                                    }),

                                Select::make('product_unit_id')
                                    ->label('Satuan')
                                    ->columnSpan(2)
                                    ->options(function (Get $get) {
                                        // Tampilkan opsi satuan khusus untuk produk yang dipilih
                                        $productId = $get('product_id');
                                        if (!$productId) return [];
                                        
                                        return ProductUnit::where('product_id', $productId)
                                            ->pluck('unit_name', 'id');
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            $unit = ProductUnit::find($state);
                                            if ($unit) {
                                                $priceStr = str_replace('.', ',', (string)(float)$unit->unit_selling_price);
                                                $set('unit_price', $priceStr);
                                            }
                                        } else {
                                            // Jika kembali ke satuan dasar
                                            $product = Product::find($get('product_id'));
                                            if ($product) {
                                                $priceStr = str_replace('.', ',', (string)(float)$product->selling_price);
                                                $set('unit_price', $priceStr);
                                            }
                                        }
                                        self::calculateItemSubtotal($set, $get);
                                    }),

                                TextInput::make('qty_billed')
                                    ->label('Qty Bayar')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateItemSubtotal($set, $get)),

                                TextInput::make('qty_bonus')
                                    ->label('Qty Bonus')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(1)
                                    ->helperText('Free'),

                                TextInput::make('unit_price')
                                    ->label('Harga Jual Satuan')
                                    ->required()
                                    ->columnSpan(2)
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->formatStateUsing(function ($state) {
                                        return $state ? str_replace('.', ',', (string)(float)$state) : null;
                                    })
                                    ->dehydrateStateUsing(fn ($state) => self::getSanitizedNumber($state))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateItemSubtotal($set, $get)),

                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->readOnly()
                                    ->columnSpan(3)
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->formatStateUsing(function ($state) {
                                        return $state ? str_replace('.', ',', (string)(float)$state) : null;
                                    })
                                    ->dehydrateStateUsing(fn ($state) => self::getSanitizedNumber($state))
                                    ->default('0,00'),
                            ])
                            ->columns(12)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateGrandTotal($get, $set)),
                            
                        TextInput::make('total_amount')
                            ->label('TOTAL PESANAN (Rp)')
                            ->readOnly()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)')) 
                            ->formatStateUsing(function ($state) {
                                return $state ? str_replace('.', ',', (string)(float)$state) : null;
                            })
                            ->dehydrateStateUsing(fn ($state) => self::getSanitizedNumber($state))
                            ->default('0,00')
                            ->extraInputAttributes(['style' => 'font-size: 1.5rem; font-weight: bold; color: green;']),
                            
                        Textarea::make('notes')
                            ->label('Catatan Pesanan')
                            ->rows(2),
                    ]),
            ]);
    }

    public static function calculateItemSubtotal(Set $set, Get $get): void
    {
        $qty = (float) ($get('qty_billed') ?: 0);
        $price = self::getSanitizedNumber($get('unit_price'));
        
        $subtotal = $qty * $price;
        $set('subtotal', str_replace('.', ',', (string)$subtotal));
        
        self::updateGrandTotal($get, $set);
    }

    public static function updateGrandTotal(Get $get, Set $set): void
    {
        $isInsideRepeater = $get('../../orderItems') !== null;
        $items = $isInsideRepeater ? $get('../../orderItems') : $get('orderItems');
            
        $total = collect($items ?? [])->sum(function ($item) {
            return self::getSanitizedNumber($item['subtotal'] ?? 0);
        });

        $formattedTotal = str_replace('.', ',', (string)$total);

        if ($isInsideRepeater) {
            $set('../../total_amount', $formattedTotal);
        } else {
            $set('total_amount', $formattedTotal);
        }
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
