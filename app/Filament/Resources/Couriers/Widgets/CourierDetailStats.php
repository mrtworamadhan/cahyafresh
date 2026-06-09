<?php

namespace App\Filament\Resources\Couriers\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class CourierDetailStats extends StatsOverviewWidget
{
    public ?Model $record = null;

    #[On('refreshCourierStats')]
    protected function getStats(): array
    {
        $totalBiaya = $this->record->deliveries()->sum('shipping_cost_actual');
        $totalHutang = $this->record->deliveries()->where('is_paid_to_courier', false)->sum('shipping_cost_actual');

        return [
            Stat::make('Total Ongkir Dihasilkan', 'Rp ' . number_format($totalBiaya, 0, ',', '.'))
                ->description('Total kontribusi kurir ini')
                ->color('success'),
            Stat::make('Hutang Belum Dibayar', 'Rp ' . number_format($totalHutang, 0, ',', '.'))
                ->description('Ongkir yang belum di-release')
                ->color('danger'),
        ];
    }
}
