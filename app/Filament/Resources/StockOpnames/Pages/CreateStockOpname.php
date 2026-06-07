<?php

namespace App\Filament\Resources\StockOpnames\Pages;

use App\Filament\Resources\StockOpnames\StockOpnameResource;
use App\Models\Product;
use App\Models\Ledger;
use App\Models\FinanceCategory;
use App\Models\StockMovement;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;
    protected function afterCreate(): void
    {
        // 1. Ambil data opname beserta item-itemnya yang baru saja di-save otomatis oleh Filament
        $opname = $this->record;
        $businessId = $opname->business_id;

        $totalKeuntungan = 0; // Total uang untuk barang yang PLUS (Ketemu)
        $totalKerugian = 0;   // Total uang untuk barang yang MINUS (Hilang)
        $totalAdjustment = 0; // Net total (plus/minus)

        // Kita bungkus pakai DB::transaction biar kalau error, datanya di-rollback semua (aman)
        DB::transaction(function () use ($opname, $businessId, &$totalKeuntungan, &$totalKerugian, &$totalAdjustment) {
            
            foreach ($opname->items as $item) {
                $product = $item->product;
                if (!$product) continue;

                // A. Catat ke tabel Stock Movement (Kartu Stok Murni)
                if ($item->difference != 0) {
                    StockMovement::create([
                        'business_id' => $businessId,
                        'product_id' => $product->id,
                        'transaction_type' => 'opname',
                        'type' => $item->difference > 0 ? 'in' : 'out', // Plus=in, Minus=out
                        'quantity' => abs($item->difference), // Masukkan selalu angka positif
                        'reason' => "Stock Opname #{$opname->id}" . ($opname->notes ? " - {$opname->notes}" : ""),
                    ]);
                }

                // B. Timpa stok lama dengan stok FISIK aktual
                $product->stock = $item->actual_stock;
                $product->save();

                // C. Kalkulasi akumulasi Uang
                $nilaiUang = (float) $item->adjustment_value; 
                $totalAdjustment += $nilaiUang;

                // Pisahkan kerugian dan keuntungan
                if ($nilaiUang > 0) {
                    $totalKeuntungan += $nilaiUang;
                } elseif ($nilaiUang < 0) {
                    $totalKerugian += abs($nilaiUang);
                }
            }

            // D. Simpan total selisih ke tabel header Opname
            $opname->update([
                'total_adjustment_value' => $totalAdjustment
            ]);

            // ========================================================
            // E. LOGIKA AKUNTANSI SAKRAL (LEDGER)
            // ========================================================

            // JIKA ADA KERUGIAN (BARANG HILANG) -> Catat sebagai Pengeluaran
            if ($totalKerugian > 0) {
                $katLoss = FinanceCategory::where('code', 'EXP_LOSS')->first();
                if ($katLoss) {
                    Ledger::create([
                        'business_id' => $businessId,
                        'wallet_id' => null, // Kasir/Dompet TIDAK DITARIk uangnya
                        'finance_category_id' => $katLoss->id,
                        'transaction_date' => $opname->opname_date,
                        'description' => "Beban Kerugian Stok (Opname #{$opname->id})",
                        'type' => 'out',
                        'amount' => $totalKerugian,
                    ]);
                }
            }

            // JIKA ADA KEUNTUNGAN (BARANG KETEMU) -> Catat sebagai Pemasukan
            if ($totalKeuntungan > 0) {
                $katGain = FinanceCategory::where('code', 'INC_OTHER')->first(); // INC_OTHER = Pendapatan Lain-lain
                if ($katGain) {
                    Ledger::create([
                        'business_id' => $businessId,
                        'wallet_id' => null, // Uang fisik laci kasir TIDAK BERTAMBAH
                        'finance_category_id' => $katGain->id,
                        'transaction_date' => $opname->opname_date,
                        'description' => "Pendapatan Penyesuaian Stok Ditemukan (Opname #{$opname->id})",
                        'type' => 'in',
                        'amount' => $totalKeuntungan,
                    ]);
                }
            }
        });
    }

    // Redirect kembali ke tabel Opname setelah simpan
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
