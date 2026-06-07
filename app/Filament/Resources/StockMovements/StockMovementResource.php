<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\StockMovements\Pages\ManageStockMovements;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;
    protected static string|UnitEnum|null $navigationGroup = 'Produk & Inventori';

    protected static ?string $navigationLabel = 'Mutasi Stok';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $tenantOwnershipRelationshipName = 'business';
    public static function canCreate(): bool
    {
        return false;
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // TextInput::make('business_id')
                //     ->required()
                //     ->numeric(),
                // TextInput::make('product_id')
                //     ->required()
                //     ->numeric(),
                // TextInput::make('transaction_type')
                //     ->required(),
                // Select::make('type')
                //     ->options(['in' => 'In', 'out' => 'Out'])
                //     ->required(),
                // TextInput::make('quantity')
                //     ->required()
                //     ->numeric(),
                // TextInput::make('reason'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')
                    ->label('Waktu Transaksi'),
                TextEntry::make('product.name')
                    ->label('Produk'),
                TextEntry::make('transaction_type')
                    ->label('Sumber Transaksi'),
                TextEntry::make('type')
                    ->label('Arus Barang'),
                TextEntry::make('quantity')
                    ->label('Jumlah Qty'),
                TextEntry::make('reason')
                    ->label('Keterangan / Alasan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('transaction_type')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'info',
                        'purchase' => 'warning',
                        'opname' => 'gray',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sale' => 'Penjualan',
                        'purchase' => 'Pembelian',
                        'opname' => 'Opname',
                        default => $state,
                    }),

                TextColumn::make('type')
                    ->label('Mutasi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'Masuk (+)',
                        'out' => 'Keluar (-)',
                    }),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->alignCenter()
                    ->weight('bold'),

                TextColumn::make('reason')
                    ->label('Keterangan')
                    ->wrap()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Filter berdasarkan Produk (Sangat berguna untuk lihat riwayat 1 barang spesifik)
                SelectFilter::make('product_id')
                    ->label('Filter Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                    
                // Filter berdasarkan Mutasi Masuk/Keluar
                SelectFilter::make('type')
                    ->label('Arus Stok')
                    ->options([
                        'in' => 'Barang Masuk',
                        'out' => 'Barang Keluar',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStockMovements::route('/'),
        ];
    }
}
