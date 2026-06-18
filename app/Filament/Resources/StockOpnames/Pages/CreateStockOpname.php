<?php

namespace App\Filament\Resources\StockOpnames\Pages;

use App\Filament\Resources\StockOpnames\StockOpnameResource;
use App\Models\Product;
use App\Models\Ledger;
use App\Models\FinanceCategory;
use App\Models\StockMovement;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;
    protected function afterCreate(): void
    {
        $opname = $this->record;
        $businessId = $opname->business_id;

        $totalKeuntungan = 0; 
        $totalKerugian = 0;   
        $totalAdjustment = 0; 

        DB::transaction(function () use ($opname, $businessId, &$totalKeuntungan, &$totalKerugian, &$totalAdjustment) {
            
            Log::info("========== START DEBUG OPNAME #{$opname->id} ==========");

            foreach ($opname->items as $item) {
                $product = $item->product;
                if (!$product) continue;

                if ($item->difference != 0) {
                    StockMovement::create([
                        'business_id' => $businessId,
                        'product_id' => $product->id,
                        'transaction_type' => 'opname',
                        'type' => $item->difference > 0 ? 'in' : 'out', 
                        'quantity' => abs($item->difference),
                        'reason' => "Stock Opname #{$opname->id}" . ($opname->notes ? " - {$opname->notes}" : ""),
                    ]);
                }

                $product->stock = $item->actual_stock;
                $product->save();

                $nilaiUang = (float) $item->adjustment_value; 
                $totalAdjustment += $nilaiUang;

                if ($nilaiUang > 0) {
                    $totalKeuntungan += $nilaiUang;
                } elseif ($nilaiUang < 0) {
                    $totalKerugian += abs($nilaiUang);
                }
            }

            $opname->update([
                'total_adjustment_value' => $totalAdjustment
            ]);

            // ==============================================================
            // INTERNALS SAFETY 1: JURNAL BEBAN KERUGIAN BARANG HILANG (EXP_LOSS)
            // ==============================================================
            if ($totalKerugian > 0) {
                // Gunakan firstOrCreate untuk menjamin kategori default selalu ada di database
                $katLoss = FinanceCategory::withoutGlobalScopes()->firstOrCreate(
                    ['code' => 'EXP_LOSS'],
                    [
                        'business_id' => null,
                        'name' => 'Beban Kerugian / Kehilangan Stok',
                        'type' => 'out',
                        'is_system' => true,
                        'is_active' => true,
                        'description' => 'Kategori sistem default untuk penyesuaian opname stok hilang'
                    ]
                );

                // PROTEKSI SAKRAL: Jika kategori tidak aktif atau gagal, batalkan paksa transaksi!
                if (!$katLoss || !$katLoss->is_active) {
                    throw new \Exception("Gagal menyimpan Stock Opname! Akun kategori 'EXP_LOSS' tidak aktif atau tidak ditemukan di sistem database. Hubungi Developer.");
                }

                Ledger::create([
                    'business_id' => $businessId,
                    'wallet_id' => null, // Non-tunai
                    'finance_category_id' => $katLoss->id,
                    'transaction_date' => $opname->opname_date ?? now(),
                    'description' => "Beban Kerugian Stok (Opname #{$opname->id})",
                    'type' => 'out',
                    'amount' => $totalKerugian,
                    'reference_type' => get_class($opname), 
                    'reference_id' => $opname->id,
                ]);
            }

            // ==============================================================
            // INTERNALS SAFETY 2: JURNAL PENDAPATAN BARANG LEBIH (INC_GAIN)
            // ==============================================================
            if ($totalKeuntungan > 0) {
                $katGain = FinanceCategory::withoutGlobalScopes()->firstOrCreate(
                    ['code' => 'INC_GAIN'],
                    [
                        'business_id' => null,
                        'name' => 'Pendapatan Selisih Lebih Stok (Opname)',
                        'type' => 'in',
                        'is_system' => true,
                        'is_active' => true,
                        'description' => 'Kategori sistem default untuk penyesuaian opname stok berlebih'
                    ]
                );

                // PROTEKSI SAKRAL: Jika kategori tidak aktif atau gagal, batalkan paksa transaksi!
                if (!$katGain || !$katGain->is_active) {
                    throw new \Exception("Gagal menyimpan Stock Opname! Akun kategori 'INC_GAIN' tidak aktif atau tidak ditemukan di sistem database. Hubungi Developer.");
                }

                Ledger::create([
                    'business_id' => $businessId,
                    'wallet_id' => null, // Non-tunai 
                    'finance_category_id' => $katGain->id,
                    'transaction_date' => $opname->opname_date ?? now(),
                    'description' => "Pendapatan Penyesuaian Stok Ditemukan (Opname #{$opname->id})",
                    'type' => 'in',
                    'amount' => $totalKeuntungan,
                    'reference_type' => get_class($opname), 
                    'reference_id' => $opname->id,
                ]);
            }

            Log::info("========== END DEBUG OPNAME #{$opname->id} ==========");
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
