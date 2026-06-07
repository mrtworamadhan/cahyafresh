<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\CustomerResource\Widgets\CustomerStatsWidget;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string
    {
        return 'Detail Pelanggan - ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['upline_code'])) {
            $upline = Customer::where('referral_code', $data['upline_code'])
                ->where('business_id', Filament::getTenant()->id)
                ->first();

            if ($upline) {
                $data['referred_by_id'] = $upline->id;
            }
        } else {
            $data['referred_by_id'] = null;
        }

        unset($data['upline_code']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerStatsWidget::class,
        ];
    }
}
