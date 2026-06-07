<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dasar Barang')
                    ->schema([
                        TextInput::make('sku')->label('Kode SKU / Barcode'),
                        TextInput::make('name')->label('Nama Barang')->required(),
                        Textarea::make('description')->label('Deskripsi'),
                    ])->columns(2),

                Section::make('Harga & Satuan Dasar')
                    ->schema([
                        TextInput::make('base_unit')
                            ->label('Satuan Dasar (Terkecil)')
                            ->placeholder('Contoh: Kg, Pcs')
                            ->required(),
                        TextInput::make('base_price')
                            ->label('HPP / Harga Modal (Per Satuan Dasar)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        TextInput::make('selling_price')
                            ->label('Harga Jual Default (Per Satuan Dasar)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        TextInput::make('stock')
                            ->label('Stok Awal')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Section::make('Konversi Satuan Tambahan (Opsional)')
                    ->description('Tambahkan jika barang ini dijual dalam satuan besar seperti Peti atau Dus.')
                    ->schema([
                        Repeater::make('units')
                            ->relationship() // Otomatis tersambung ke relasi units() di model
                            ->schema([
                                TextInput::make('unit_name')
                                    ->label('Nama Satuan (Contoh: Peti)')
                                    ->required(),
                                TextInput::make('conversion_value')
                                    ->label('Isi (Berapa jumlah satuan dasarnya?)')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Contoh: Jika 1 Peti = 13 Kg, isi angka 13.'),
                                TextInput::make('unit_selling_price')
                                    ->label('Harga Jual (Per Satuan)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->default(0),
                            ])
                            ->columns(3)
                            ->addActionLabel('Tambah Satuan Lain')
                    ])->columnSpanFull(),
            ]);
    }
}
