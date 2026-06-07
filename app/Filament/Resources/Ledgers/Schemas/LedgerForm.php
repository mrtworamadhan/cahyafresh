<?php

namespace App\Filament\Resources\Ledgers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LedgerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Select::make('business_id')
                //     ->relationship('business', 'name')
                //     ->required(),
                // Select::make('wallet_id')
                //     ->relationship('wallet', 'name')
                //     ->required(),
                // DatePicker::make('transaction_date')
                //     ->required(),
                // TextInput::make('description')
                //     ->required(),
                // Select::make('type')
                //     ->options(['in' => 'In', 'out' => 'Out'])
                //     ->required(),
                // TextInput::make('amount')
                //     ->required()
                //     ->numeric(),
                // TextInput::make('contact_type'),
                // TextInput::make('contact_id')
                //     ->numeric(),
                // TextInput::make('reference_type'),
                // TextInput::make('reference_id')
                //     ->numeric(),
            ]);
    }
}
