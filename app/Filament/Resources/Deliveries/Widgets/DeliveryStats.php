<?php

namespace App\Filament\Resources\Deliveries\Widgets;

use App\Models\Delivery;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeliveryStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;

        $totalBeban = Delivery::where('business_id', $businessId)->sum('shipping_cost_actual');
        $totalHutang = Delivery::where('business_id', $businessId)->where('is_paid_to_courier', false)->sum('shipping_cost_actual');

        return [
            Stat::make('Total Beban Biaya Ongkir', 'Rp ' . number_format($totalBeban, 0, ',', '.'))
                ->description('Total ongkir riil keseluruhan')
                ->color('success'),
            Stat::make('Tanggungan Belum Lunas', 'Rp ' . number_format($totalHutang, 0, ',', '.'))
                ->description('Ongkir yang belum dibayar ke Kurir/Vendor')
                ->color('danger'),
        ];
    }
}
