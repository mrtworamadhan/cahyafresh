<?php

namespace App\Filament\Resources\PoBatches\Pages;

use App\Filament\Resources\PoBatches\PoBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPoBatches extends ListRecords
{
    protected static string $resource = PoBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
