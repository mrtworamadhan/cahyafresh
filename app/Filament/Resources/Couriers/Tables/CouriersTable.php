<?php

namespace App\Filament\Resources\Couriers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouriersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;
                return $query->where('business_id', $businessId);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kurir')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'primary' => 'internal',
                        'warning' => 'external',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'internal' => 'Internal',
                        'external' => 'Pihak Ketiga',
                        default => $state,
                    }),

                TextColumn::make('vehicle_plate')
                    ->label('Plat Nomor')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable()
                    ->placeholder('-'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Filter Tipe')
                    ->options([
                        'internal' => 'Internal',
                        'external' => 'Pihak Ketiga',
                    ]),
            ])
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
