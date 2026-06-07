<?php

namespace App\Filament\Resources\Wallets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WalletForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Dompet / Rekening')
                    ->placeholder('Contoh: Kasir Utama, BCA, Kas Kecil')
                    ->required()
                    ->maxLength(255),

                Select::make('type')
                    ->label('Tipe Dompet')
                    ->options([
                        'bank' => 'Bank',
                        'cash' => 'Kas',
                    ])
                    ->required(),
                TextInput::make('account_number')
                    ->label('Nomor Rekening')
                    ->placeholder('Contoh: 1234567890')
                    ->visible(fn ($get) => $get('type') === 'bank')
                    ->maxLength(255),

                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true),
            ]);
    }
}
