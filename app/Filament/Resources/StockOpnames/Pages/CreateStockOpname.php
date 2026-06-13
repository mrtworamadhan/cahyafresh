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
            Log::info("Business ID saat ini: " . $businessId);

            foreach ($opname->items as $item) {
                $product = $item->product;
                if (!$product) {
                    Log::warning("Item Opname ID {$item->id} dilewati karena Product tidak ditemukan (NULL).");
                    continue;
                }

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

                Log::info("Product: {$product->name} | Selisih Stok: {$item->difference} | Nilai Adjustment Uang: {$nilaiUang}");

                if ($nilaiUang > 0) {
                    $totalKeuntungan += $nilaiUang;
                } elseif ($nilaiUang < 0) {
                    $totalKerugian += abs($nilaiUang);
                }
            }

            $opname->update([
                'total_adjustment_value' => $totalAdjustment
            ]);

            Log::info("--- HASIL AKUMULASI OPNAME ---");
            Log::info("Total Keuntungan (Barang Lebih): " . $totalKeuntungan);
            Log::info("Total Kerugian (Barang Hilang): " . $totalKerugian);
            Log::info("Total Grand Adjustment: " . $totalAdjustment);

            // ==============================================================
            // 1. JURNAL BEBAN KERUGIAN BARANG HILANG (EXP_LOSS)
            // ==============================================================
            if ($totalKerugian > 0) {
                Log::info("Mencoba mencari Kategori COA dengan kode 'EXP_LOSS'...");
                
                $katLoss = FinanceCategory::withoutGlobalScopes()
                    ->where('code', 'EXP_LOSS')
                    ->where(function($query) use ($businessId) {
                        $query->where('business_id', $businessId)
                            ->orWhereNull('business_id'); 
                    })
                    ->first();
                
                Log::info("Hasil pencarian COA 'EXP_LOSS': " . ($katLoss ? "DITEMUKAN (ID: {$katLoss->id})" : "TIDAK DITEMUKAN (NULL)"));

                if ($katLoss) {
                    Ledger::create([
                        'business_id' => $businessId,
                        'wallet_id' => null, // Non-tunai (Tidak memotong kas/bank)
                        'finance_category_id' => $katLoss->id,
                        'transaction_date' => $opname->opname_date ?? now(),
                        'description' => "Beban Kerugian Stok (Opname #{$opname->id})",
                        'type' => 'out',
                        'amount' => $totalKerugian,
                        'reference_type' => get_class($opname), // <--- BARU: Kunci Audit Trail
                        'reference_id' => $opname->id,          // <--- BARU: Kunci Audit Trail
                    ]);
                    Log::info("Ledger Kerugian BERHASIL dibuat.");
                } else {
                    Log::warning("Ledger Kerugian GAGAL dibuat karena \$katLoss bernilai NULL.");
                }
            }

            // ==============================================================
            // 2. JURNAL PENDAPATAN BARANG LEBIH / DITEMUKAN (INC_GAIN)
            // ==============================================================
            if ($totalKeuntungan > 0) {
                // PERBAIKAN FATAL: Mengubah kode pencarian dari INC_OTHER ke INC_GAIN sesuai COA baru
                Log::info("Mencoba mencari Kategori COA dengan kode 'INC_GAIN'...");
                
                $katGain = FinanceCategory::withoutGlobalScopes()
                    ->where('code', 'INC_GAIN')
                    ->where(function($query) use ($businessId) {
                        $query->where('business_id', $businessId)
                            ->orWhereNull('business_id');
                    })
                    ->first();
                
                Log::info("Hasil pencarian COA 'INC_GAIN': " . ($katGain ? "DITEMUKAN (ID: {$katGain->id})" : "TIDAK DITEMUKAN (NULL)"));

                if ($katGain) {
                    Ledger::create([
                        'business_id' => $businessId,
                        'wallet_id' => null, // Non-tunai 
                        'finance_category_id' => $katGain->id,
                        'transaction_date' => $opname->opname_date ?? now(),
                        'description' => "Pendapatan Penyesuaian Stok Ditemukan (Opname #{$opname->id})",
                        'type' => 'in',
                        'amount' => $totalKeuntungan,
                        'reference_type' => get_class($opname), // <--- BARU: Kunci Audit Trail
                        'reference_id' => $opname->id,          // <--- BARU: Kunci Audit Trail
                    ]);
                    Log::info("Ledger Keuntungan BERHASIL dibuat.");
                } else {
                    Log::warning("Ledger Keuntungan GAGAL dibuat karena \$katGain bernilai NULL.");
                }
            }

            Log::info("========== END DEBUG OPNAME #{$opname->id} ==========");
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
