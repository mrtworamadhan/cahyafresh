<?php

namespace App\Filament\Resources\Ledgers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LedgersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y H:i')
                    ->sortable(),
                    
                TextColumn::make('wallet.name')
                    ->label('Rekening')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap(), 
                    
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Uang Masuk',
                        'out' => 'Uang Keluar',
                    }),
                    
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn ($record) => $record->type === 'in' ? 'success' : 'danger')
                    ->weight('bold'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('wallet_id')
                    ->label('Filter Dompet')
                    ->relationship('wallet', 'name'),
                    
                SelectFilter::make('type')
                    ->label('Filter Jenis')
                    ->options([
                        'in' => 'Uang Masuk Saja',
                        'out' => 'Uang Keluar Saja',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
