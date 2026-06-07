<?php

namespace App\Filament\Resources\Couriers;

use App\Filament\Resources\Couriers\Pages\CreateCourier;
use App\Filament\Resources\Couriers\Pages\EditCourier;
use App\Filament\Resources\Couriers\Pages\ListCouriers;
use App\Filament\Resources\Couriers\Schemas\CourierForm;
use App\Filament\Resources\Couriers\Tables\CouriersTable;
use App\Models\Courier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CourierResource extends Resource
{
    protected static ?string $model = Courier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Logistik';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Armada & Kurir';
    protected static ?string $pluralModelLabel = 'Armada & Kurir';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CourierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouriersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCouriers::route('/'),
            'create' => CreateCourier::route('/create'),
            'edit' => EditCourier::route('/{record}/edit'),
        ];
    }
}
