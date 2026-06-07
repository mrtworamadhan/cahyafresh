<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Wallet;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;

class DashboardStatsWidget extends BaseWidget
{
    // Agar widget ini muncul paling atas
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;

        // 1. Total Saldo Kas (Semua Laci & Rekening)
        $totalKas = Wallet::where('business_id', $businessId)->sum('balance');

        // 2. Valuasi Stok Gudang (Modal saat ini x Sisa Stok Fisik)
        $valuasiStok = Product::where('business_id', $businessId)->sum(DB::raw('stock * base_price'));

        // 3. Omzet Penjualan (Hanya nota yang sudah Selesai di Bulan Ini)
        $omzetBulanIni = Order::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        // 4. HPP Historis (Harga Modal dari barang-barang yang terjual bulan ini)
        $hppBulanIni = OrderItem::whereHas('order', function($q) use ($businessId) {
                $q->where('business_id', $businessId)
                  ->where('status', 'completed')
                  ->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
            })
            ->sum(DB::raw('base_price * qty_billed'));

        // 5. Laba Kotor (Omzet - HPP)
        $labaKotor = $omzetBulanIni - $hppBulanIni;

        return [
            Stat::make('Total Saldo Kas', 'Rp ' . number_format($totalKas, 0, ',', '.'))
                ->description('Uang fisik & di rekening')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Valuasi Aset Stok', 'Rp ' . number_format($valuasiStok, 0, ',', '.'))
                ->description('Nilai modal barang di gudang')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Omzet (Bulan Ini)', 'Rp ' . number_format($omzetBulanIni, 0, ',', '.'))
                ->description('Pendapatan kotor bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Laba Kotor (Bulan Ini)', 'Rp ' . number_format($labaKotor, 0, ',', '.'))
                ->description('Omzet dikurangi HPP Historis')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),
        ];
    }
}