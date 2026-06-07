<?php

namespace App\Filament\Resources\PoBatches;

use App\Filament\Resources\PoBatches\Pages\CreatePoBatch;
use App\Filament\Resources\PoBatches\Pages\EditPoBatch;
use App\Filament\Resources\PoBatches\Pages\ListPoBatches;
use App\Filament\Resources\PoBatches\Schemas\PoBatchForm;
use App\Filament\Resources\PoBatches\Tables\PoBatchesTable;
use App\Filament\Resources\PoBatchResource\RelationManagers\OrdersRelationManager;
use App\Models\PoBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PoBatchResource extends Resource
{
    protected static ?string $model = PoBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PoBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PoBatchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPoBatches::route('/'),
            'create' => CreatePoBatch::route('/create'),
            'edit' => EditPoBatch::route('/{record}/edit'),
        ];
    }
}
