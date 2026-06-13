<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\FinanceCategory;
use App\Models\Delivery;

class AccountingReconcileCommand extends Command
{
    // Nama perintah yang akan dijalankan di terminal
    protected $signature = 'accounting:reconcile';
    protected $description = 'Menyeimbangkan otomatis data transaksi masa lalu dengan arsitektur keuangan akrual yang baru';

    public function handle()
    {
        $this->info('======================================================');
        $this->info('🚀 MEMULAI PROSES REKONSILIASI KEUANGAN OTOMATIS...');
        $this->info('======================================================');

        DB::transaction(function () {
            
            // ==============================================================
            // TAHAP 1: PASTIKAN KATEGORI BARU SUDAH TERSEDIA DI DATABASE
            // ==============================================================
            $this->comment('👉 Tahap 1: Memeriksa kelengkapan master kategori keuangan...');
            
            $newCategories = [
                ['code' => 'INC_GAIN', 'name' => 'Pendapatan Selisih Lebih Stok (Opname)', 'type' => 'in'],
                ['code' => 'LIA_COMMISSION_PAID', 'name' => 'Pencairan Komisi / Withdraw Agen (Pelunasan)', 'type' => 'out'],
                ['code' => 'LIA_SHIPPING_PAID', 'name' => 'Pelunasan Ongkir / Bayar Cash ke Kurir', 'type' => 'out'],
                ['code' => 'OP_CSR_ZAKAT', 'name' => 'Beban Zakat, Infaq, Sedekah & CSR (Pengakuan Biaya)', 'type' => 'out'],
                ['code' => 'LIA_CSR_ZAKAT_PAID', 'name' => 'Penyaluran Dana Zakat / CSR (Pelunasan Cadangan)', 'type' => 'out'],
            ];

            foreach ($newCategories as $cat) {
                FinanceCategory::firstOrCreate(
                    ['code' => $cat['code']],
                    [
                        'business_id' => null,
                        'name' => $cat['name'],
                        'type' => $cat['type'],
                        'is_system' => true,
                        'is_active' => true,
                        'description' => 'Kategori sistem hasil rekonsiliasi penyesuaian'
                    ]
                );
            }
            $this->info('✅ Master kategori keuangan sudah lengkap.');

            // Tarik ID Kategori untuk mapping penyesuaian
            $catOpCommission = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_COMMISSION')->first()?->id;
            $catLiaCommPaid  = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_COMMISSION_PAID')->first()?->id;
            $catOpShipping   = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first()?->id;
            $catLiaShipPaid  = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_SHIPPING_PAID')->first()?->id;

            // ==============================================================
            // TAHAP 2: KOREKSI SALAH AKUN PADA PENCAIRAN MASA LALU (PHASE 2)
            // ==============================================================
            $this->comment("\n" . '👉 Tahap 2: Mengoreksi salah akun pada pencairan komisi & ongkir kurir lama...');

            // A. Perbaiki Withdraw Komisi (Dulu memakai OP_COMMISSION padahal wallet_id terisi tunai)
            if ($catOpCommission && $catLiaCommPaid) {
                $affectedComm = Ledger::where('finance_category_id', $catOpCommission)
                    ->whereNotNull('wallet_id') // Berarti uang beneran rilis keluar dompet
                    ->update(['finance_category_id' => $catLiaCommPaid]);
                
                $this->info("⚡ Berhasil memindahkan {$affectedComm} baris log pencairan komisi ke akun pelunasan liabilitas.");
            }

            // B. Perbaiki Release Ongkir Kurir (Dulu memakai OP_SHIPPING padahal wallet_id terisi tunai)
            if ($catOpShipping && $catLiaShipPaid) {
                $affectedShip = Ledger::where('finance_category_id', $catOpShipping)
                    ->whereNotNull('wallet_id')
                    ->update(['finance_category_id' => $catLiaShipPaid]);

                $this->info("⚡ Berhasil memindahkan {$affectedShip} baris log rilis ongkir kurir ke akun pelunasan liabilitas.");
            }

            // ==============================================================
            // TAHAP 3: BACKFILL PENGAKUAN BEBAN GANTUNG MASA LALU (PHASE 1)
            // ==============================================================
            $this->comment("\n" . '👉 Tahap 3: Melakukan scanning & suntik otomatis beban gantung yang belum tercatat...');

            // Ambil seluruh orderan completed masa lalu yang dibuat sebelum kodingan diperbaiki
            $completedOrders = Order::where('status', 'completed')->get();
            $insertedCommissionCount = 0;
            $insertedShippingCount = 0;

            foreach ($completedOrders as $order) {
                
                // A. Suntik Jurnal Pengakuan Beban Komisi (Jika dulu terlewat belum mencatat Ledger)
                if ($order->commission_amount > 0 && $order->commission_recipient_id && $catOpCommission) {
                    
                    // Cek apakah di database lama sudah melahirkan log komisi non-tunai
                    $existCommLedger = Ledger::where('reference_type', Order::class)
                        ->where('reference_id', $order->id)
                        ->where('finance_category_id', $catOpCommission)
                        ->whereNull('wallet_id')
                        ->exists();

                    if (!$existCommLedger) {
                        Ledger::create([
                            'business_id' => $order->business_id,
                            'wallet_id' => null, // Set gantung (Phase 1)
                            'finance_category_id' => $catOpCommission,
                            'transaction_date' => $order->updated_at ?? $order->order_date,
                            'description' => "Pengakuan Beban Komisi Nota: {$order->order_number} (Auto-Reconcile)",
                            'type' => 'out',
                            'amount' => (float) $order->commission_amount,
                            'contact_type' => Customer::class,
                            'contact_id' => $order->commission_recipient_id,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);
                        $insertedCommissionCount++;
                    }
                }

                // B. Suntik Jurnal Pengakuan Beban Ongkir Kurir Actual (Jika dulu terlewat belum mencatat Ledger)
                if ($order->delivery_type === 'delivery' && (float)$order->shipping_cost_actual > 0 && $catOpShipping) {
                    
                    $existShipLedger = Ledger::where('reference_type', Order::class)
                        ->where('reference_id', $order->id)
                        ->where('finance_category_id', $catOpShipping)
                        ->whereNull('wallet_id')
                        ->exists();

                    if (!$existShipLedger) {
                        Ledger::create([
                            'business_id' => $order->business_id,
                            'wallet_id' => null, // Set gantung (Phase 1)
                            'finance_category_id' => $catOpShipping,
                            'transaction_date' => $order->updated_at ?? $order->order_date,
                            'description' => "Pengakuan Beban Pengiriman/Kurir Nota: {$order->order_number} (Auto-Reconcile)",
                            'type' => 'out',
                            'amount' => (float) $order->shipping_cost_actual,
                            'reference_type' => Order::class,
                            'reference_id' => $order->id,
                        ]);
                        $insertedShippingCount++;
                    }
                }
            }

            $this->info("⚡ Berhasil menyuntikkan {$insertedCommissionCount} baris gantung Beban Komisi baru.");
            $this->info("⚡ Berhasil menyuntikkan {$insertedShippingCount} baris gantung Beban Ongkir Kurir baru.");
        });

        $this->info("\n" . '======================================================');
        $this->info('🎉 REKONSILIASI SELESAI! SEMUA DATA KRONOLOGIS SUDAH SEIMBANG');
        $this->info('======================================================');
    }
}