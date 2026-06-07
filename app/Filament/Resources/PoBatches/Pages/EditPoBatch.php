<?php

namespace App\Filament\Resources\PoBatches\Pages;

use App\Filament\Resources\PoBatches\PoBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPoBatch extends EditRecord
{
    protected static string $resource = PoBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
