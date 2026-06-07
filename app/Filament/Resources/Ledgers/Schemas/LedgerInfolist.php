<?php

namespace App\Filament\Resources\Ledgers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LedgerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('wallet.name')
                    ->label('Wallet'),
                TextEntry::make('transaction_date')
                    ->date(),
                TextEntry::make('description'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('contact_type')
                    ->placeholder('-'),
                TextEntry::make('contact_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reference_type')
                    ->placeholder('-'),
                TextEntry::make('reference_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
