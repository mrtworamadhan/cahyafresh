<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Order';

    protected static ?string $relatedResource = CustomerResource::class;

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
                    ->label('No. Invoice')
                    ->searchable(),
                    
                TextColumn::make('order_date')
                    ->label('Tanggal Nota')
                    ->date('d M Y'),
                    
                TextColumn::make('total_amount')
                    ->label('Total Nota')
                    ->money('IDR'),
                    
                TextColumn::make('remaining_balance')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),
                    
                TextColumn::make('status')
                    ->label('Status Barang')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'canceled' => 'danger',
                    }),
                    
                TextColumn::make('payment_status')
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
                // Tombol View untuk melihat isi detail barang di dalam nota tersebut
                ViewAction::make(),
            ]);
    }
}
