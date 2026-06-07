<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LedgersRelationManager extends RelationManager
{
    protected static string $relationship = 'ledgers';

    protected static ?string $title = 'Transaksi';

    public function form(Schema $schema): Schema
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('transaction_date')->label('Tanggal')->date('d M Y'),
                TextColumn::make('description')->label('Keterangan')->wrap(),
                TextColumn::make('wallet.name')->label('Via'),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'in' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state === 'in' ? 'Masuk' : 'Keluar'),
                TextColumn::make('amount')->label('Nominal')->money('IDR'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
