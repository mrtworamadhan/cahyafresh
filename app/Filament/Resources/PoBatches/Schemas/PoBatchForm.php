<?php

namespace App\Filament\Resources\PoBatches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PoBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kelompok PO')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kelompok PO')
                            ->placeholder('Contoh: PO Parsel Lebaran Gelombang 1')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(), // Biar kotaknya panjang penuh
                            
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),
                            
                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai / Tutup PO')
                            ->required(),
                            
                        Select::make('status')
                            ->label('Status Gelombang')
                            ->options([
                                'open' => 'Buka (Menerima Pesanan)',
                                'closed' => 'Tutup (Selesai)',
                            ])
                            ->default('open')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
