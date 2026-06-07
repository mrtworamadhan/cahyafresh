<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Supplier / Pemasok')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Nomor Telepon / WA')
                    ->tel()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label('Alamat Lengkap')
                    ->columnSpanFull(),
            ]);
    }
}
