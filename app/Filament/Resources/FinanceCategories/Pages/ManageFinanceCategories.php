<?php

namespace App\Filament\Resources\FinanceCategories\Pages;

use App\Filament\Resources\FinanceCategories\FinanceCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFinanceCategories extends ManageRecords
{
    protected static string $resource = FinanceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
