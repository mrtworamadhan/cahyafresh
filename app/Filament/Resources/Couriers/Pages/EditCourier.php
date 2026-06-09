<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Resources\Couriers\CourierResource;
use App\Filament\Resources\Couriers\Widgets\CourierDetailStats;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourier extends EditRecord
{
    protected static string $resource = CourierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CourierDetailStats::class, // <--- TAMBAHKAN INI
        ];
    }
}
