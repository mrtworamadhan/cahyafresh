<?php

namespace App\Filament\Resources\Suppliers\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchase;

class SupplierStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $supplier = $this->record;

        if (!$supplier) {
            return [];
        }

        // 1. Hitung Total Tagihan & Total Dibayar untuk mencari PIUTANG
        $totalPurchase = $supplier->purchases()->sum('total_amount');
        $totalPaid = $supplier->ledgers()
            ->where('reference_type', Purchase::class)
            ->where('type', 'out')
            ->sum('amount');
            
        $hutang = $totalPurchase - $totalPaid;

        // 2. Ambil Saldo Deposit
        $deposit = $supplier->deposit_balance;

        // 3. Hitung Jumlah Transaksi
        $jumlahNota = $supplier->purchases()->count();

        return [
            Stat::make('Total Hutang (Belum Dibayar)', 'Rp ' . number_format($hutang, 0, ',', '.'))
                ->description('Sisa tagihan yang harus ditagih')
                ->descriptionIcon($hutang > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-check-circle')
                ->color($hutang > 0 ? 'danger' : 'success'),

            Stat::make('Saldo Deposit', 'Rp ' . number_format($deposit, 0, ',', '.'))
                ->description('Uang Deposit di Supplier')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('info'),

            Stat::make('Total Nilai Belanja', 'Rp ' . number_format($totalPurchase, 0, ',', '.'))
                ->description($jumlahNota . ' Nota Pesanan telah dibuat')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),
        ];
    }
}