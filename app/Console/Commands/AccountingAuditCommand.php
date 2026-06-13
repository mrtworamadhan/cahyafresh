<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\FinanceCategory;
use Illuminate\Support\Facades\DB;

class AccountingAuditCommand extends Command
{
    protected $signature = 'accounting:audit';
    protected $description = 'Mendeteksi titik kebocoran data penyebab Neraca tidak balance';

    public function handle()
    {
        $this->info('======================================================');
        $this->info('🔍 MEMULAI AUDIT FORENSIK TRANSAKSI KEUANGAN...');
        $this->info('======================================================');

        $businessId = auth()->user()?->businesses()?->first()?->id ?? 1; // Sesuaikan default tenant lu
        $hasLeak = false;

        // ------------------------------------------------------------------
        // TES 1: AUDIT DOMPET & WALLET (Kas Fisik vs Riwayat Kasir)
        // ------------------------------------------------------------------
        $this->comment('1. Memeriksa Integritas Saldo Dompet/Bank...');
        $wallets = Wallet::all();
        
        foreach ($wallets as $wallet) {
            // Hitung berapa seharusnya saldo berdasarkan mutasi Ledger masuk - keluar
            $cashIn = Ledger::where('wallet_id', $wallet->id)->where('type', 'in')->sum('amount');
            $cashOut = Ledger::where('wallet_id', $wallet->id)->where('type', 'out')->sum('amount');
            $expectedBalance = $cashIn - $cashOut;

            $selisihWallet = (float)$wallet->balance - $expectedBalance;

            if (abs($selisihWallet) > 0.01) {
                $this->error("❌ [BOCOR] Dompet '{$wallet->name}' Tidak Sinkron!");
                $this->line("   - Saldo Nyata di Kolom Tabel: Rp " . number_format($wallet->balance, 0, ',', '.'));
                $this->line("   - Saldo Seharusnya (Dari Riwayat Ledger): Rp " . number_format($expectedBalance, 0, ',', '.'));
                $this->line("   - Selisih Ghaib: Rp " . number_format($selisihWallet, 0, ',', '.'));
                $this->line("   💡 Solusi: Saldo wallet pernah diedit manual, atau ada slip ledger tunai yang terhapus.");
                $hasLeak = true;
            } else {
                $this->info("✅ [PASS] Dompet '{$wallet->name}' klop seimbang.");
            }
        }

        // ------------------------------------------------------------------
        // TES 2: AUDIT HUTANG KOMISI (Saldo Agen vs Jurnal Gantung)
        // ------------------------------------------------------------------
        $this->comment("\n" . '2. Memeriksa Sinkronisasi Saldo Komisi Agen...');
        $catOpCommission = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_COMMISSION')->first()?->id;
        $catLiaCommPaid  = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_COMMISSION_PAID')->first()?->id;

        if ($catOpCommission && $catLiaCommPaid) {
            $customers = Customer::where('commission_balance', '>', 0)->get();
            
            foreach ($customers as $customer) {
                // Total Komisi yang didapat (Gantung Phase 1)
                $totalEarned = Ledger::where('contact_id', $customer->id)
                    ->where('contact_type', Customer::class)
                    ->where('finance_category_id', $catOpCommission)
                    ->sum('amount');

                // Total Komisi yang sudah dicairkan tunai (Phase 2)
                $totalWithdrawn = Ledger::where('contact_id', $customer->id)
                    ->where('contact_type', Customer::class)
                    ->where('finance_category_id', $catLiaCommPaid)
                    ->sum('amount');

                $expectedCommBalance = $totalEarned - $totalWithdrawn;
                $selisihComm = (float)$customer->commission_balance - $expectedCommBalance;

                if (abs($selisihComm) > 0.01) {
                    $this->error("❌ [BOCOR] Saldo Komisi Agen '{$customer->name}' Pincang!");
                    $this->line("   - Saldo Komisi di Tabel Customer: Rp " . number_format($customer->commission_balance, 0, ',', '.'));
                    $this->line("   - Saldo Komisi Hitungan Log Ledger: Rp " . number_format($expectedCommBalance, 0, ',', '.'));
                    $this->line("   - Selisih Ghaib: Rp " . number_format($selisihComm, 0, ',', '.'));
                    $this->line("   💡 Solusi: Dulu ada pesanan dibatalkan tapi saldo komisi agen lupa dipotong, atau tombol cairkan komisi di-klik ganda.");
                    $hasLeak = true;
                }
            }
            if (!$hasLeak) $this->info("✅ [PASS] Seluruh saldo hutang komisi agen klop akurat.");
        }

        // ------------------------------------------------------------------
        // TES 3: AUDIT HUTANG ONGKIR KURIR (Surat Jalan vs Jurnal Gantung)
        // ------------------------------------------------------------------
        $this->comment("\n" . '3. Memeriksa Sinkronisasi Hutang Surat Jalan Kurir...');
        $catOpShipping  = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first()?->id;
        $catLiaShipPaid = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_SHIPPING_PAID')->first()?->id;

        if ($catOpShipping && $catLiaShipPaid) {
            // Hitung total pengakuan beban kurir seumur hidup
            $totalBebanKurir = Ledger::where('finance_category_id', $catOpShipping)->sum('amount');
            // Hitung total rilis uang cash ke kurir seumur hidup
            $totalRilisKurir = Ledger::where('finance_category_id', $catLiaShipPaid)->sum('amount');
            $expectedHutangOngkir = $totalBebanKurir - $totalRilisKurir;

            // Hitung sisa manifest surat jalan yang belum dibayar di tabel logistik
            $realHutangOngkir = Delivery::where('is_paid_to_courier', false)
                ->whereHas('order', function($q) { $q->where('status', 'completed'); })
                ->sum('shipping_cost_actual');

            $selisihOngkir = $realHutangOngkir - $expectedHutangOngkir;

            if (abs($selisihOngkir) > 0.01) {
                $this->error("❌ [BOCOR] Siklus Hutang Ongkir Kurir Tidak Sinkron!");
                $this->line("   - Hutang Riil Manifest Surat Jalan: Rp " . number_format($realHutangOngkir, 0, ',', '.'));
                $this->line("   - Hutang Seharusnya Berdasarkan Ledger: Rp " . number_format($expectedHutangOngkir, 0, ',', '.'));
                $this->line("   - Selisih Ghaib: Rp " . number_format($selisihOngkir, 0, ',', '.'));
                $this->line("   💡 Solusi: Ada nota PO yang shipping_cost_actual-nya diedit setelah statusnya completed.");
                $hasLeak = true;
            } else {
                $this->info("✅ [PASS] Siklus hutang pengiriman kurir klop akurat.");
            }
        }

        // ------------------------------------------------------------------
        // TES 4: AUDIT MUTASI PHANTOM (Transfer Bank Pincang Sebelah)
        // ------------------------------------------------------------------
        $this->comment("\n" . '4. Memeriksa Validitas Transfer Antar-Rekening (Mutasi Internal)...');
        
        $phantomTransfers = Ledger::whereNull('finance_category_id')
            ->where('type', 'out')
            ->get();

        $phantomCount = 0;
        foreach ($phantomTransfers as $outLedger) {
            
            // PERBAIKAN: Paksa teks tanggal berubah menjadi Objek Carbon secara aman
            $txDate = \Carbon\Carbon::parse($outLedger->transaction_date);
            $startTime = $txDate->copy()->subSeconds(5);
            $endTime = $txDate->copy()->addSeconds(5);

            // Cari pasangan 'in' yang nominal dan detiknya mirip (toleransi 5 detik)
            $hasPair = Ledger::whereNull('finance_category_id')
                ->where('type', 'in')
                ->where('amount', $outLedger->amount)
                ->where('wallet_id', '!=', $outLedger->wallet_id)
                ->whereBetween('transaction_date', [
                    $startTime, 
                    $endTime
                ])
                ->exists();

            if (!$hasPair) {
                $this->error("❌ [BOCOR] Ditemukan Mutasi Pincang Sebelah!");
                $this->line("   - ID Ledger: {$outLedger->id} | Tgl: {$outLedger->transaction_date}");
                $this->line("   - Keterangan: {$outLedger->description} | Nominal: Rp " . number_format($outLedger->amount, 0, ',', '.'));
                $this->line("   💡 Solusi: Uang ditransfer keluar dari Dompet A, tapi baris ledger uang masuk ke Dompet B gagal terbuat.");
                $phantomCount++;
                $hasLeak = true;
            }
        }

        if ($phantomCount === 0) {
            $this->info("✅ [PASS] Seluruh transfer antar rekening memiliki pasangan lengkap.");
        }

        // ------------------------------------------------------------------
        // KESIMPULAN AUDIT
        // ------------------------------------------------------------------
        $this->line("\n======================================================");
        if ($hasLeak) {
            $this->error("🚨 KESIMPULAN: DITEMUKAN KEBOCORAN HISTORIS DATA!");
            $this->line("   Silakan perbaiki poin-poin merah di atas langsung di database.");
        } else {
            $this->info("🎉 KESIMPULAN: CORE DATABASE LU SUDAH 100% STERIL & SEHAT!");
            $this->line("   Jika neraca masih selisih, periksa rumus penjumlahan di file LaporanKeuangan.php.");
        }
        $this->line("======================================================");
    }
}