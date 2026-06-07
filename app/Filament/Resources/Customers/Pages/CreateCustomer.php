<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['upline_code'])) {
            $upline = Customer::where('referral_code', $data['upline_code'])
                ->where('business_id', Filament::getTenant()->id)
                ->first();

            if ($upline) {
                $data['referred_by_id'] = $upline->id;
            }
        }
        unset($data['upline_code']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
