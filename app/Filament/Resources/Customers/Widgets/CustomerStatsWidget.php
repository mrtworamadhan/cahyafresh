<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order;

class CustomerStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $customer = $this->record;

        if (!$customer) {
            return [];
        }

        // 1. Hitung Total Tagihan & Total Dibayar untuk mencari PIUTANG
        $totalOrder = $customer->orders()->sum('total_amount');
        $totalPaid = $customer->ledgers()
            ->where('reference_type', Order::class)
            ->where('type', 'in')
            ->sum('amount');
            
        $piutang = $totalOrder - $totalPaid;

        // 2. Ambil Saldo Deposit
        $deposit = $customer->deposit_balance;

        // 3. Hitung Jumlah Transaksi
        $jumlahNota = $customer->orders()->count();

        return [
            Stat::make('Total Piutang (Belum Dibayar)', 'Rp ' . number_format($piutang, 0, ',', '.'))
                ->description('Sisa tagihan yang harus ditagih')
                ->descriptionIcon($piutang > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-check-circle')
                ->color($piutang > 0 ? 'danger' : 'success'),

            Stat::make('Saldo Deposit', 'Rp ' . number_format($deposit, 0, ',', '.'))
                ->description('Uang titipan pelanggan')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('info'),

            Stat::make('Saldo Komisi (Belum Dicairkan)', 'Rp ' . number_format($customer->commission_balance, 0, ',', '.'))
                ->description('Tabungan komisi dari referral')
                ->descriptionIcon('heroicon-m-gift')
                ->color('warning'),

            Stat::make('Total Nilai Belanja', 'Rp ' . number_format($totalOrder, 0, ',', '.'))
                ->description($jumlahNota . ' Nota Pesanan telah dibuat')
                ->descriptionIcon('heroicon-m-shopping-bag')    
                ->color('success'),
        ];
    }
}