<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Delivery;
use App\Models\FinanceCategory;
use Illuminate\Support\Facades\DB;

class AccountingAuditCommand extends Command
{
    protected $signature = 'accounting:audit';
    protected $description = 'Audit forensik menyeluruh untuk mendeteksi ketidakseimbangan sistem keuangan POS & ERP';

    public function handle()
    {
        $this->info('====================================================================');
        $this->info('🔍 MEMULAI AUDIT FORENSIK CROSS-TABEL MENYELURUH (ANTI-TAMBAL)...');
        $this->info('====================================================================');

        $businessId = 1; // Sesuaikan tenant utama lu
        $hasLeak = false;

        // ------------------------------------------------------------------
        // TES 1: AUDIT INTEGRITAS SALDO DOMPET/BANK
        // ------------------------------------------------------------------
        $this->comment('👉 1. Memeriksa Sinkronisasi Saldo Dompet Fisik vs Mutasi Ledger...');
        $wallets = Wallet::where('business_id', $businessId)->get();
        foreach ($wallets as $wallet) {
            $cashIn = Ledger::where('business_id', $businessId)->where('wallet_id', $wallet->id)->where('type', 'in')->sum('amount');
            $cashOut = Ledger::where('business_id', $businessId)->where('wallet_id', $wallet->id)->where('type', 'out')->sum('amount');
            $expectedBalance = $cashIn - $cashOut;
            $selisihWallet = (float)$wallet->balance - $expectedBalance;

            if (abs($selisihWallet) > 0.01) {
                $this->error("   ❌ [MISMATCH] Saldo tabel Wallet '{$wallet->name}' tidak cocok dengan log mutasi ledger!");
                $this->line("      Seharusnya: Rp " . number_format($expectedBalance, 2) . " | Tercatat: Rp " . number_format($wallet->balance, 2));
                $hasLeak = true;
            }
        }
        if (!$hasLeak) $this->info("   ✅ [PASS] Semua saldo dompet tunai/bank klop seimbang.");

        // ------------------------------------------------------------------
        // TES 2: DETEKSI PINCOK DUNIA OPERASIONAL VS LEDGER SALES (PENDAPATAN)
        // ------------------------------------------------------------------
        $this->comment("\n" . '👉 2. Menyelidiki Sinkronisasi Data Penjualan (Orders vs Ledgers)...');
        
        // Hitung total penjualan kotor dari tabel operasional orders
        $totalOmzetOrders = Order::where('business_id', $businessId)->where('status', 'completed')->sum('total_amount');
        
        // Hitung berapa penambahan piutang/kas yang dicatat oleh ledger keuangan untuk orders tersebut
        $catSalesId = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_SALES')->first()?->id;
        $catArId    = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_AR')->first()?->id;
        
        $totalLedgerSales = Ledger::where('business_id', $businessId)
            ->whereIn('finance_category_id', [$catSalesId, $catArId])
            ->where('type', 'in')
            ->where('reference_type', 'App\Models\Order')
            ->sum('amount');

        $selisihSalesLog = $totalOmzetOrders - $totalLedgerSales;
        // Kita juga hitung total piutang yang saat ini aktif menggantung di tabel order
        $piutangAktifOrders = Order::where('business_id', $businessId)->where('status', 'completed')->whereIn('payment_status', ['unpaid', 'partial'])->get()->sum('remaining_balance');

        $this->line("   - Total Omzet di Tabel Orders : Rp " . number_format($totalOmzetOrders, 2, ',', '.'));
        $this->line("   - Total Piutang Aktif (Order) : Rp " . number_format($piutangAktifOrders, 2, ',', '.'));
        
        // ------------------------------------------------------------------
        // TES 3: DETEKSI PINCOK RECONCILE STOCK GUDANG VS AKUNTANSI
        // ------------------------------------------------------------------
        $this->comment("\n" . '👉 3. Menyelidiki Sinkronisasi Nilai Gudang (Products vs Opname/HPP)...');
        
        $nilaiFisikGudang = Product::where('business_id', $businessId)->sum(DB::raw('stock * base_price'));
        
        // Hitung akumulasi penyesuaian nilai uang dari ledger opname
        $catLossId = FinanceCategory::withoutGlobalScopes()->where('code', 'EXP_LOSS')->first()?->id;
        $catGainId = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_GAIN')->first()?->id;
        
        $ledgerGain = Ledger::where('business_id', $businessId)->where('finance_category_id', $catGainId)->where('type', 'in')->sum('amount');
        $ledgerLoss = Ledger::where('business_id', $businessId)->where('finance_category_id', $catLossId)->where('type', 'out')->sum('amount');
        $netOpnameLedger = $ledgerGain - $ledgerLoss;

        $this->line("   - Nilai Fisik Gudang Saat Ini (Stock * Base Price): Rp " . number_format($nilaiFisikGudang, 2, ',', '.'));
        $this->line("   - Akumulasi Bersih Jurnal Opname di Ledger         : Rp " . number_format($netOpnameLedger, 2, ',', '.'));

        // ------------------------------------------------------------------
        // TES 4: REKONSTRUKSI ULANG FORMULA NERACA LAJUR UNTUK SIMULASI SELISIH
        // ------------------------------------------------------------------
        $this->comment("\n" . '👉 4. Simulasi Pembongkaran Komponen Rumus LaporanKeuangan.php...');
        
        $kas = Wallet::where('business_id', $businessId)->sum('balance');
        $totalPiutang = Order::where('business_id', $businessId)->where('status', 'completed')->whereIn('payment_status', ['unpaid', 'partial'])->get()->sum('remaining_balance');
        $stok = Product::where('business_id', $businessId)->sum(DB::raw('stock * base_price'));
        $totalAktiva = $kas + $totalPiutang + $stok;

        $totalHutangUsaha = Purchase::where('business_id', $businessId)->whereIn('status', ['unpaid', 'partial'])->get()->sum('remaining_balance');
        $hutangKomisi = Customer::where('business_id', $businessId)->sum('commission_balance');
        $hutangOngkir = Delivery::where('business_id', $businessId)->where('is_paid_to_courier', false)->whereHas('order', function($q) { $q->where('status', 'completed'); })->sum('shipping_cost_actual');
        $kewajiban = $totalHutangUsaha + $hutangKomisi + $hutangOngkir;

        $modalCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'EQ_MODAL')->first();
        $modalAwal = Ledger::where('business_id', $businessId)->where('finance_category_id', $modalCategory?->id)->sum('amount');
        $priveCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'EQ_PRIVE')->first();
        $prive = Ledger::where('business_id', $businessId)->where('finance_category_id', $priveCategory?->id)->sum('amount');

        // Kalkulasi Laba Bersih yang dipakai oleh file LaporanKeuangan.php lu
        $shippingCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first();
        $bebanOngkirMurni = Ledger::where('business_id', $businessId)->where('finance_category_id', $shippingCategory?->id)->sum('amount');
        $pendapatanLedgerMurni = Ledger::query()->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')->where('ledgers.business_id', $businessId)->where('ledgers.type', 'in')->whereIn('finance_categories.code', ['INC_GAIN', 'INC_OTHER'])->sum('ledgers.amount');
        $omzetBarangMurni = Order::where('business_id', $businessId)->where('status', 'completed')->sum(DB::raw('total_amount - shipping_fee_billed'));
        $omzetOngkirMurni = Order::where('business_id', $businessId)->where('status', 'completed')->sum('shipping_fee_billed');
        $hppMurni = OrderItem::whereHas('order', function($q) { $q->where('status', 'completed'); })->sum(DB::raw('base_price * (qty_billed + qty_bonus)'));
        $totalBebanMurni = Ledger::query()->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')->where('ledgers.business_id', $businessId)->where('ledgers.type', 'out')->whereNotIn('finance_categories.code', ['EXP_PURCHASE', 'LIA_AP', 'ASSET_DEP_SUPPLIER', 'OP_SHIPPING', 'LIA_COMMISSION_PAID', 'LIA_SHIPPING_PAID', 'LIA_CSR_ZAKAT_PAID', 'EQ_MODAL', 'EQ_PRIVE'])->sum('ledgers.amount') + $bebanOngkirMurni;

        $labaBersihSeumurHidup = ($omzetBarangMurni + $omzetOngkirMurni + $pendapatanLedgerMurni - $hppMurni) - $totalBebanMurni;
        $ekuitasBuku = $modalAwal + $labaBersihSeumurHidup - $prive;

        $selisihPersamaanNeraca = $totalAktiva - ($kewajiban + $ekuitasBuku);

        $this->line("   ----------------------------------------------------------------");
        $this->line("   TOTAL AKTIVA (KIRI) : Rp " . number_format($totalAktiva, 2, ',', '.'));
        $this->line("   TOTAL PASIVA (KANAN): Rp " . number_format(($kewajiban + $ekuitasBuku), 2, ',', '.'));
        $this->line("   STATUS SELISIH REAL : Rp " . number_format($selisihPersamaanNeraca, 2, ',', '.'));
        $this->line("   ----------------------------------------------------------------");

        // DIAGNOSTIC CORE RADAR DETECTOR (MENCARI MATEMATIKA SELISIH)
        $this->comment("\n" . '👉 5. KESIMPULAN FORENSIK DETEKSI KEPINCANGAN...');
        
        // Deteksi Celah 1: Apakah ada Order Completed tapi HPP-nya terhitung Rp 0 karena base_price produk 0?
        if ($hppMurni == 0 && $omzetBarangMurni > 0) {
            $this->warn("   ⚠️ DETEKSI A: Nilai total HPP seumur hidup terbaca Rp 0,00! Ini artinya semua master produk lu kolom 'base_price'-nya bernilai 0. Efeknya, Laba Berjalan lu melompat terlalu tinggi di sebelah kanan!");
        }

        // Deteksi Celah 2: Apakah ada ketidakcocokan antara hutang di purchase dengan ledger pembelian?
        $totalInvoicePurchase = Purchase::where('business_id', $businessId)->sum('total_amount');
        $catPurchaseId = FinanceCategory::withoutGlobalScopes()->where('code', 'EXP_PURCHASE')->first()?->id;
        $catApId       = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_AP')->first()?->id;
        $totalPaidSupplierLedger = Ledger::where('business_id', $businessId)->whereIn('finance_category_id', [$catPurchaseId, $catApId])->where('type', 'out')->sum('amount');
        $sisaHutangTabelPurchase = Purchase::where('business_id', $businessId)->whereIn('status', ['unpaid', 'partial'])->get()->sum('remaining_balance');

        $expectedHutangSistem = $totalInvoicePurchase - $totalPaidSupplierLedger;
        $deltaHutang = $sisaHutangTabelPurchase - $expectedHutangSistem;

        if (abs($deltaHutang) > 0.01) {
            $this->warn("   ⚠️ DETEKSI B: Ketidakselarasan Aliran Pembelian Supplier!");
            $this->line("      Total Invoice Kulaan: Rp " . number_format($totalInvoicePurchase, 2));
            $this->line("      Total Uang yang Keluar via Ledger: Rp " . number_format($totalPaidSupplierLedger, 2));
            $this->line("      Sisa Hutang Seharusnya: Rp " . number_format($expectedHutangSistem, 2));
            $this->line("      Sisa Hutang di Tabel Purchase Saat Ini: Rp " . number_format($sisaHutangTabelPurchase, 2));
            $this->line("      Selisih Mismatch Pembelian: Rp " . number_format($deltaHutang, 2));
        }

        $this->line("====================================================================\n");
    }
}