<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Nota Pembelian')
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label('Nomor Nota / Invoice')
                            ->required()
                            ->default('PO-' . date('YmdHis')), 
                        
                        DatePicker::make('purchase_date')
                            ->label('Tanggal Belanja')
                            ->default(now())
                            ->required(),
                        
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Select::make('status')
                            ->label('Status Pembayaran')
                            ->options([
                                'paid' => 'Lunas (Cash)',
                                'unpaid' => 'Belum Lunas (Hutang / Tempo)',
                            ])
                            ->default('unpaid')
                            ->required(),
                    ])->columns(2),

                Section::make('Daftar Barang (Item Belanja)')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('purchaseItems')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Pilih Produk')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                // Ubah harga db "125000.00" ke string "125000,00" agar dibaca mulus oleh mask
                                                $basePriceStr = str_replace('.', ',', (string)(float)$product->base_price);
                                                $set('unit_price', $basePriceStr);
                                                
                                                $qty = (float) ($get('quantity') ?: 1);
                                                $price = (float) $product->base_price;
                                                
                                                $subtotal = $qty * $price;
                                                $set('subtotal', str_replace('.', ',', (string)$subtotal));
                                                
                                                self::updateGrandTotal($get, $set);
                                            }
                                        }
                                    }),
                                
                                TextInput::make('quantity')
                                    ->label('Jumlah')
                                    ->numeric() // Di quantity ->numeric() tetap aman karena bukan uang
                                    ->required()
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $qty = (float) $state;
                                        $price = self::getSanitizedNumber($get('unit_price'));
                                        
                                        $subtotal = $qty * $price;
                                        // Lempar state sebagai string dengan koma agar UI ter-update sempurna
                                        $set('subtotal', str_replace('.', ',', (string)$subtotal));
                                        
                                        self::updateGrandTotal($get, $set);
                                    }),
                                
                                TextInput::make('unit_price')
                                    ->label('Harga Modal Satuan')
                                    ->required()
                                    ->prefix('Rp')
                                    // PENTING: ->numeric() DIHAPUS dari sini
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->formatStateUsing(function ($state) {
                                        return $state ? str_replace('.', ',', (string)(float)$state) : null;
                                    })
                                    ->dehydrateStateUsing(fn ($state) => self::getSanitizedNumber($state))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $qty = (float) ($get('quantity') ?: 1);
                                        $price = self::getSanitizedNumber($state); 
                                        
                                        $subtotal = $qty * $price;
                                        $set('subtotal', str_replace('.', ',', (string)$subtotal));
                                        
                                        self::updateGrandTotal($get, $set);
                                    }),
                                
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->readOnly()
                                    ->prefix('Rp')
                                    // PENTING: ->numeric() DIHAPUS dari sini
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->formatStateUsing(function ($state) {
                                        return $state ? str_replace('.', ',', (string)(float)$state) : null;
                                    })
                                    ->dehydrateStateUsing(fn ($state) => self::getSanitizedNumber($state))
                                    ->default('0,00'),
                            ])
                            ->columns(4)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateGrandTotal($get, $set)),
                            
                        TextInput::make('total_amount')
                            ->label('TOTAL NOTA (Rp)')
                            ->readOnly()
                            ->prefix('Rp')
                            // PENTING: ->numeric() DIHAPUS dari sini
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)')) 
                            ->formatStateUsing(function ($state) {
                                return $state ? str_replace('.', ',', (string)(float)$state) : null;
                            })
                            ->dehydrateStateUsing(fn ($state) => self::getSanitizedNumber($state))
                            ->default('0,00')
                            ->extraInputAttributes(['style' => 'font-size: 1.5rem; font-weight: bold; color: green;']),
                    ]),
            ]);
    }

    public static function updateGrandTotal(Get $get, Set $set): void
    {
        $isInsideRepeater = $get('../../purchaseItems') !== null;
        $items = $isInsideRepeater ? $get('../../purchaseItems') : $get('purchaseItems');
            
        $total = collect($items ?? [])->sum(function ($item) {
            return self::getSanitizedNumber($item['subtotal'] ?? 0);
        });

        // Set state ke format yang ada komanya agar dirender rapih oleh Mask
        $formattedTotal = str_replace('.', ',', (string)$total);

        if ($isInsideRepeater) {
            $set('../../total_amount', $formattedTotal);
        } else {
            $set('total_amount', $formattedTotal);
        }
    }

    /**
     * Helper untuk memastikan nilai string dikonversi ke float database yang valid.
     */
    private static function getSanitizedNumber($value): float
    {
        if (empty($value)) {
            return 0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        // 1. Buang semua titik (pemisah ribuan) -> "1350000,00"
        // 2. Ubah koma menjadi titik (standar desimal PHP) -> "1350000.00"
        $cleanString = str_replace(['.', ','], ['', '.'], (string) $value);

        return (float) $cleanString;
    }
}