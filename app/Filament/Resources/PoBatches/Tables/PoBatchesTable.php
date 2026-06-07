<?php

namespace App\Filament\Resources\PoBatches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PoBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama PO')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('start_date')
                    ->label('Tgl Mulai')
                    ->date('d M Y')
                    ->sortable(),
                    
                TextColumn::make('end_date')
                    ->label('Tgl Selesai')
                    ->date('d M Y')
                    ->sortable(),
                    
                TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('Jumlah Pesanan')
                    ->badge()
                    ->color('primary'),
                    
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Buka',
                        'closed' => 'Tutup',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
