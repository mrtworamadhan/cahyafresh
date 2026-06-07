<?php

namespace App\Filament\Widgets;

use App\Models\Ledger;
use Filament\Widgets\ChartWidget;
use Filament\Facades\Filament;

class CashflowChartWidget extends ChartWidget
{
    protected ?string $heading = 'Arus Kas (30 Hari Terakhir)';
    
    // Taruh di bawah kotak statistik
    protected static ?int $sort = 2; 
    
    // Bikin layarnya lebar penuh di Dashboard
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $businessId = Filament::getTenant()?->id ?? auth()->user()->businesses()->first()?->id;

        $dataMasuk = [];
        $dataKeluar = [];
        $labels = [];

        // Tarik data 30 hari ke belakang dari hari ini
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->translatedFormat('d M'); // Contoh: 12 Jun

            // Hitung semua uang masuk di tanggal tersebut
            $masuk = Ledger::where('business_id', $businessId)
                ->whereDate('transaction_date', $dateString)
                ->where('type', 'in')
                ->sum('amount');

            // Hitung semua uang keluar di tanggal tersebut
            $keluar = Ledger::where('business_id', $businessId)
                ->whereDate('transaction_date', $dateString)
                ->where('type', 'out')
                ->sum('amount');

            $dataMasuk[] = $masuk;
            $dataKeluar[] = $keluar;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Uang Masuk (Omzet/Deposit/Suntik)',
                    'data' => $dataMasuk,
                    'borderColor' => '#10b981', // Hijau
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)', // Hijau transparan
                    'fill' => true,
                    'tension' => 0.4, // Bikin garisnya melengkung halus
                ],
                [
                    'label' => 'Uang Keluar (Belanja/Prive/Operasional)',
                    'data' => $dataKeluar,
                    'borderColor' => '#ef4444', // Merah
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)', // Merah transparan
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // Pakai Line Chart (Grafik Garis)
        return 'line';
    }
}