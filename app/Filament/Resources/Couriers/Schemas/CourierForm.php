<?php

namespace App\Filament\Resources\Couriers\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Armada / Kurir')
                    ->schema([
                        Hidden::make('business_id')
                            ->default(fn () => Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id),

                        TextInput::make('name')
                            ->label('Nama Kurir / Ekspedisi')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->label('Tipe Ekspedisi')
                            ->options([
                                'internal' => 'Internal (Armada Sendiri)',
                                'external' => 'Eksternal (Pihak Ketiga/Vendor)',
                            ])
                            ->required()
                            ->default('internal'),

                        TextInput::make('vehicle_plate')
                            ->label('Plat Nomor Kendaraan (Opsional)')
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Nomor WhatsApp / HP (Opsional)')
                            ->tel()
                            ->maxLength(255),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])->columns(2),
            ]);
    }
}
