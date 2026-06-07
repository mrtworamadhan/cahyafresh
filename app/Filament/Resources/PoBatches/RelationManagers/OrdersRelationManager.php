<?php

namespace App\Filament\Resources\PoBatchResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Schema $form): Schema
    {
        return $form->schema([]); 
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. Nota')
                    ->searchable(),
                    
                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable(),
                    
                TextColumn::make('order_date')
                    ->label('Tanggal')
                    ->date('d M Y'),
                    
                TextColumn::make('total_amount')
                    ->label('Total Belanja')
                    ->money('IDR'),
                    
                TextColumn::make('status')
                    ->label('Status Pesanan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'canceled' => 'danger',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Kita hapus tombol 'Create' di sini agar kasir tidak bingung
            ])
            ->actions([
                // Tombol untuk langsung mengintip detail barangnya
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }
}