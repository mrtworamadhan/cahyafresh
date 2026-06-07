<?php

namespace App\Filament\Resources\StockOpnames\Schemas;

use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StockOpnameForm
{
    public static function configure(Schema $schema): Schema
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
        return $schema
            ->components([
                Section::make('Informasi Dokumen')
                    ->schema([
                        DatePicker::make('opname_date')
                            ->label('Tanggal Opname')
                            ->default(now())
                            ->required(),
                        TextInput::make('notes')
                            ->label('Catatan / Keterangan')
                            ->placeholder('Contoh: Opname rutin akhir bulan Januari')
                            ->columnSpan(2),
                        Hidden::make('user_id')
                            ->default(auth()->id()),
                        Hidden::make('business_id')
                            ->default($businessId),
                    ])->columns(3),

                Section::make('Daftar Barang Opname')
                    ->description('Pilih barang dan ketik stok fisik. Selisih dan nilai HPP akan dihitung otomatis.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('')
                            ->addActionLabel('Tambah Barang')
                            ->schema([
                                // 1. Kolom Produk
                                Select::make('product_id')
                                    ->label('Produk')
                                    ->options(Product::where('business_id', $businessId)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live() 
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('system_stock', $product->stock);
                                                $set('hpp', $product->base_price);
                                                
                                                $actual = $get('actual_stock');
                                                if ($actual !== null && $actual !== '') {
                                                    $diff = (int)$actual - (int)$product->stock;
                                                    $set('difference', $diff);
                                                    $set('adjustment_value', $diff * $product->base_price);
                                                } else {
                                                    $set('difference', 0);
                                                    $set('adjustment_value', 0);
                                                }
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                // 2. Kolom Stok Sistem (Readonly)
                                TextInput::make('system_stock')
                                    ->label('Sistem')
                                    ->numeric()
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'bg-gray-100 dark:bg-gray-800'])
                                    ->columnSpan(1),

                                // 3. Kolom Stok Fisik (Input)
                                TextInput::make('actual_stock')
                                    ->label('Fisik')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true) 
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $sys = $get('system_stock');
                                        $hpp = $get('hpp');
                                        
                                        if ($state !== null && $state !== '' && $sys !== null) {
                                            $diff = (int)$state - (int)$sys;
                                            $set('difference', $diff);
                                            $set('adjustment_value', $diff * (float)$hpp);
                                        } else {
                                            $set('difference', 0);
                                            $set('adjustment_value', 0);
                                        }
                                    })
                                    ->columnSpan(1),

                                // ==========================================
                                // TAMPILAN UI (Placeholder & HTMLString)
                                // ==========================================
                                Placeholder::make('difference_display')
                                    ->label('Selisih')
                                    ->content(function (Get $get) {
                                        $diff = (int) $get('difference');
                                        // Gunakan kode HEX Warna CSS Langsung: Merah (#dc2626) & Hijau (#16a34a)
                                        $style = $diff < 0 ? 'color: #dc2626;' : ($diff > 0 ? 'color: #16a34a;' : 'color: #4b5563;');
                                        $sign = $diff > 0 ? '+' : '';
                                        
                                        return new \Illuminate\Support\HtmlString("<span style='{$style}' class='font-bold text-lg'>{$sign}{$diff}</span>");
                                    })
                                    ->columnSpan(1),

                                Placeholder::make('adjustment_value_display')
                                    ->label('Nominal (Rp)')
                                    ->content(function (Get $get) {
                                        $val = (float) $get('adjustment_value');
                                        $color = $val < 0 ? 'text-danger-600' : ($val > 0 ? 'text-success-600' : 'text-gray-600');
                                        $sign = $val > 0 ? '+' : '';
                                        $formatted = number_format(abs($val), 0, ',', '.');
                                        
                                        return new \Illuminate\Support\HtmlString("<span class='font-bold {$color} text-lg'>{$sign}Rp {$formatted}</span>");
                                    })
                                    ->columnSpan(1),
                                // ==========================================
                                // DATA RAHASIA UNTUK DATABASE
                                // ==========================================
                                Hidden::make('difference')->default(0),
                                Hidden::make('adjustment_value')->default(0),
                                Hidden::make('hpp')->default(0),
                            ])
                            ->columns(6) 
                            ->defaultItems(1)
                            ->cloneable(),
                    ]),

                // --- FOOTER: TOTAL SUM SAKTI ---
                Section::make('Total Evaluasi Opname')
                    ->schema([
                        Placeholder::make('total_summary')
                            ->label('Selsih Total')
                            ->size('xl')
                            ->content(function (Get $get) {
                                // Ambil semua data dari repeater 'items'
                                $items = $get('items');
                                $total = 0;
                                
                                if (is_array($items)) {
                                    foreach ($items as $item) {
                                        $total += (float) ($item['adjustment_value'] ?? 0);
                                    }
                                }

                                // Format tampilan berdasarkan nilai Total
                                $formattedTotal = number_format(abs($total), 0, ',', '.');

                                if ($total < 0) {
                                    return new \Illuminate\Support\HtmlString(
                                        "<div class='text-right w-full'>
                                            <span style='color: #dc2626;' class='font-black text-3xl'>- Rp {$formattedTotal}</span>
                                        </div>"
                                    );
                                } elseif ($total > 0) {
                                    return new \Illuminate\Support\HtmlString(
                                        "<div class='text-right w-full'>
                                            <span style='color: #16a34a;' class='font-black text-3xl'>+ Rp {$formattedTotal}</span>
                                        </div>"
                                    );
                                }
                                
                                return new \Illuminate\Support\HtmlString(
                                    "<div class='text-right w-full'>
                                        <span style='color: #4b5563;' class='font-black text-3xl'>Rp 0</span>
                                    </div>"
                                );
                            }),
                    ]),
            ]);
    }
}
