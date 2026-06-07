<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchases';

    protected static ?string $title = 'Purchase';

    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Nota Supplier')
                    ->searchable(),
                    
                TextColumn::make('purchase_date')
                    ->label('Tanggal Belanja')
                    ->date('d M Y'),
                    
                TextColumn::make('total_amount')
                    ->label('Total Belanja')
                    ->money('IDR'),
                    
                TextColumn::make('remaining_balance')
                    ->label('Sisa Hutang')
                    ->money('IDR')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),
                    
                TextColumn::make('status')
                    ->label('Status Bayar')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unpaid' => 'Belum Lunas',
                        'partial' => 'Dicicil',
                        'paid' => 'Lunas',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
            ]);
    }
}
