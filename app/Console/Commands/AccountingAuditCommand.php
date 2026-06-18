<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\Supplier;
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
        $this->info('🔍 MEMULAI AUDIT FORENSIK DAN REKONSTRUKSI NERACA MENYELURUH...');
        $this->info('====================================================================');

        $businessId = 1; // Sesuaikan tenant utama lu
        $hasLeak = false;

        // ------------------------------------------------------------------
        // TES 1: AUDIT DOMPET & WALLET (Kas Fisik vs Riwayat Kasir)
        // ------------------------------------------------------------------
        $this->comment('👉 1. Memeriksa Integritas Saldo Dompet/Bank...');
        $wallets = Wallet::where('business_id', $businessId)->get();
        foreach ($wallets as $wallet) {
            $cashIn = Ledger::where('business_id', $businessId)->where('wallet_id', $wallet->id)->where('type', 'in')->sum('amount');
            $cashOut = Ledger::where('business_id', $businessId)->where('wallet_id', $wallet->id)->where('type', 'out')->sum('amount');
            $expectedBalance = $cashIn - $cashOut;
            $selisihWallet = (float)$wallet->balance - $expectedBalance;

            if (abs($selisihWallet) > 0.01) {
                $this->error("   ❌ [BOCOR] Dompet '{$wallet->name}' Tidak Sinkron!");
                $this->line("      - Saldo Riil di Kolom Tabel : Rp " . number_format($wallet->balance, 2, ',', '.'));
                $this->line("      - Saldo Seharusnya via Ledger: Rp " . number_format($expectedBalance, 2, ',', '.'));
                $this->line("      - Selisih Ghaib             : Rp " . number_format($selisihWallet, 2, ',', '.'));
                $hasLeak = true;
            } else {
                $this->info("   ✅ [PASS] Dompet '{$wallet->name}' klop seimbang.");
            }
        }

        // ------------------------------------------------------------------
        // TES 2: AUDIT INTEGRITAS DOKUMEN HUTANG SUPPLIER (PURCHASES vs LEDGERS)
        // ------------------------------------------------------------------
        $this->comment("\n" . '👉 2. Memeriksa Validitas Aliran Dana Pembayaran Supplier (Purchases)...');
        $purchases = Purchase::where('business_id', $businessId)->get();
        $purchaseLeakCount = 0;

        foreach ($purchases as $p) {
            // Hitung berapa uang keluar riil dari ledger untuk nota pembelian ini
            $totalPaidViaLedger = Ledger::where('reference_type', get_class($p))
                ->where('reference_id', $p->id)
                ->where('type', 'out')
                ->sum('amount');

            $calculatedRemaining = (float)$p->total_amount - $totalPaidViaLedger;
            
            // Cek sisa hutang menggunakan logika model / data berjalan
            $modelRemaining = method_exists($p, 'getRemainingBalanceAttribute') ? $p->remaining_balance : ($p->total_amount - $totalPaidViaLedger);

            // Deteksi jika ada status 'paid' tapi ternyata uang keluar di ledger belum melunasi total tagihan
            if ($p->status === 'paid' && $calculatedRemaining > 0.01) {
                $this->error("   ❌ [LEAK] Nota PO #{$p->invoice_number} Berstatus 'PAID' Tapi Belum Lunas!");
                $this->line("      - Total Tagihan Asli : Rp " . number_format($p->total_amount, 2, ',', '.'));
                $this->line("      - Total Terbayar Kasir: Rp " . number_format($totalPaidViaLedger, 2, ',', '.'));
                $this->line("      - Sisa Hutang Gantung : Rp " . number_format($calculatedRemaining, 2, ',', '.'));
                $purchaseLeakCount++;
                $hasLeak = true;
            }
        }
        if ($purchaseLeakCount === 0) {
            $this->info("   ✅ [PASS] Seluruh dokumen transaksi & cicilan supplier sinkron.");
        }

        // ------------------------------------------------------------------
        // TES 3: AUDIT MUTASI PHANTOM DAN LEAK KATEGORI (Mendeteksi Kasus Ledger 330)
        // ------------------------------------------------------------------
        $this->comment("\n" . '👉 3. Memeriksa Kebocoran Kategori COA / Transfer Ghaib...');
        $nullCategoryLedgers = Ledger::where('business_id', $businessId)
            ->whereNull('finance_category_id')
            ->get();

        $categoryLeakCount = 0;
        foreach ($nullCategoryLedgers as $ledger) {
            if ($ledger->reference_type) {
                // Kasus Kasir Lu: Kategori Kosong tapi punya reference dokumen komersial
                $this->warn("   ⚠️ [COA LEAK] Ledger ID #{$ledger->id} Tidak Memiliki Kategori Keuangan (NULL)!");
                $this->line("      - Tanggal/Jam : {$ledger->transaction_date} | Tipe: {$ledger->type}");
                $this->line("      - Keterangan  : '{$ledger->description}' | Nominal: Rp " . number_format($ledger->amount, 2, ',', '.'));
                $this->line("      - Dokumen Jalur: Ref Model '{$ledger->reference_type}' (ID: {$ledger->reference_id})");
                $this->line("      💡 Analisis: Ini bukan bocor transfer bank, tapi kasir membayar nota tanpa memasukkan ID Kategori Akuntansi.");
                $categoryLeakCount++;
                $hasLeak = true;
            } else {
                // Murni transfer internal bank pincang sebelah
                $txDate = \Carbon\Carbon::parse($ledger->transaction_date);
                $hasPair = Ledger::whereNull('finance_category_id')->where('type', $ledger->type === 'out' ? 'in' : 'out')->where('amount', $ledger->amount)->where('wallet_id', '!=', $ledger->wallet_id)->whereBetween('transaction_date', [$txDate->copy()->subSeconds(5), $txDate->copy()->addSeconds(5)])->exists();
                if (!$hasPair) {
                    $this->error("   ❌ [PHANTOM] Transfer Bank ID #{$ledger->id} Sebelah Pincang!");
                    $hasLeak = true;
                }
            }
        }
        if ($categoryLeakCount === 0 && $nullCategoryLedgers->count() === 0) {
            $this->info("   ✅ [PASS] Seluruh baris transaksi memiliki identitas kategori COA yang lengkap.");
        }

        // ------------------------------------------------------------------
        // TES 4: REKONSTRUKSI MATEMATIKA FORMULA NERACA SAKRAL (THE SMOKE TEST)
        // ------------------------------------------------------------------
        $this->comment("\n" . '👉 4. Merekonstruksi Persamaan Neraca Lajur (Live Equation Simulation)...');
        
        // Sisi Aktiva
        $kas = Wallet::where('business_id', $businessId)->sum('balance');
        $totalPiutang = Order::where('business_id', $businessId)->where('status', 'completed')->whereIn('payment_status', ['unpaid', 'partial'])->get()->sum('remaining_balance');
        $stok = Product::where('business_id', $businessId)->sum(DB::raw('stock * base_price'));
        $depositSup = Supplier::where('business_id', $businessId)->sum('deposit_balance');
        $totalAktiva = $kas + $totalPiutang + $stok + $depositSup;

        // Sisi Pasiva (Kewajiban)
        $totalHutangUsaha = Purchase::where('business_id', $businessId)->whereIn('status', ['unpaid', 'partial'])->get()->sum('remaining_balance');
        $depositPel = Customer::where('business_id', $businessId)->sum('deposit_balance');
        $hutangKomisi = Customer::where('business_id', $businessId)->sum('commission_balance');
        $hutangOngkir = Delivery::where('business_id', $businessId)->where('is_paid_to_courier', false)->whereHas('order', function($q) { $q->where('status', 'completed'); })->sum('shipping_cost_actual');
        $kewajiban = $totalHutangUsaha + $depositPel + $hutangKomisi + $hutangOngkir;

        // Sisi Pasiva (Ekuitas / Laba Seumur Hidup)
        $modalCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'EQ_MODAL')->first();
        $modalAwal = Ledger::where('business_id', $businessId)->where('finance_category_id', $modalCategory?->id)->sum('amount');
        $priveCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'EQ_PRIVE')->first();
        $prive = Ledger::where('business_id', $businessId)->where('finance_category_id', $priveCategory?->id)->sum('amount');

        $shippingCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first();
        $bebanOngkirMurni = Ledger::where('business_id', $businessId)->where('finance_category_id', $shippingCategory?->id)->sum('amount');

        $pendapatanLedgerMurni = Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)->where('ledgers.type', 'in')
            ->whereIn('finance_categories.code', ['INC_GAIN', 'INC_OTHER'])->sum('ledgers.amount');

        $omzetBarangMurni = Order::where('business_id', $businessId)->where('status', 'completed')->sum(DB::raw('total_amount - shipping_fee_billed'));
        $omzetOngkirMurni = Order::where('business_id', $businessId)->where('status', 'completed')->sum('shipping_fee_billed');
        $hppMurni = OrderItem::whereHas('order', function($q) use ($businessId) { $q->where('business_id', $businessId)->where('status', 'completed'); })->sum(DB::raw('base_price * (qty_billed + qty_bonus)'));
        
        $totalBebanMurni = Ledger::query()
            ->join('finance_categories', 'ledgers.finance_category_id', '=', 'finance_categories.id')
            ->where('ledgers.business_id', $businessId)->where('ledgers.type', 'out')
            ->whereNotIn('finance_categories.code', ['EXP_PURCHASE', 'LIA_AP', 'ASSET_DEP_SUPPLIER', 'OP_SHIPPING', 'LIA_COMMISSION_PAID', 'LIA_SHIPPING_PAID', 'LIA_CSR_ZAKAT_PAID', 'EQ_MODAL', 'EQ_PRIVE'])
            ->sum('ledgers.amount') + $bebanOngkirMurni;

        $labaBersihSeumurHidup = ($omzetBarangMurni + $omzetOngkirMurni + $pendapatanLedgerMurni - $hppMurni) - $totalBebanMurni;
        $ekuitasBuku = $modalAwal + $labaBersihSeumurHidup - $prive;

        $selisihPersamaanNeraca = $totalAktiva - ($kewajiban + $ekuitasBuku);

        // Tampilkan Laporan Simulasi Struktural Neraca
        $this->line("   ----------------------------------------------------------------");
        $this->line("   [SISI AKTIVA / ASSET]                         [SISI PASIVA / KEWAJIBAN & MODAL]");
        $this->line("   + Saldo Kas/Bank : Rp " . str_pad(number_format($kas, 0, ',', '.'), 16) . " | + Hutang Usaha : Rp " . number_format($totalHutangUsaha, 0, ',', '.'));
        $this->line("   + Total Piutang  : Rp " . str_pad(number_format($totalPiutang, 0, ',', '.'), 16) . " | + Deposit Cust : Rp " . number_format($depositPel, 0, ',', '.'));
        $this->line("   + Nilai Stok/Inv : Rp " . str_pad(number_format($stok, 0, ',', '.'), 16) . " | + Hutang Comm  : Rp " . number_format($hutangKomisi, 0, ',', '.'));
        $this->line("   + Deposit Supp   : Rp " . str_pad(number_format($depositSup, 0, ',', '.'), 16) . " | + Hutang Ongkir: Rp " . number_format($hutangOngkir, 0, ',', '.'));
        $this->line("                                                   | + Modal Awal   : Rp " . number_format($modalAwal, 0, ',', '.'));
        $this->line("                                                   | + Laba Berjalan: Rp " . number_format($labaBersihSeumurHidup, 0, ',', '.'));
        $this->line("                                                   | - Prive Owner  : Rp " . number_format($prive, 0, ',', '.'));
        $this->line("   ----------------------------------------------------------------");
        $this->line("   TOTAL AKTIVA     : Rp " . str_pad(number_format($totalAktiva, 2, ',', '.'), 16) . " | TOTAL PASIVA   : Rp " . number_format(($kewajiban + $ekuitasBuku), 2, ',', '.'));
        
        if (abs($selisihPersamaanNeraca) > 0.01) {
            $this->error("   ⚠️ HASIL PERSAMAAN NERACA: SELISIH Rp " . number_format($selisihPersamaanNeraca, 2, ',', '.'));
            $hasLeak = true;
        } else {
            $this->info("   ✅ HASIL PERSAMAAN NERACA: 100% BALANCE (Rp 0,00)");
        }

        // ------------------------------------------------------------------
        // KESIMPULAN STRUKTURAL KESELURUHAN
        // ------------------------------------------------------------------
        $this->line("\n====================================================================");
        if ($hasLeak) {
            $this->error("🚨 KESIMPULAN: FORENSIK BERHASIL MENANGKAP TITIK MASALAH!");
            $this->line("   Jangan lakukan patch/heal dahulu. Analisis rincian angka di atas.");
        } else {
            $this->info("🎉 KESIMPULAN: SELURUH STRUKTUR LOGIKA DATA LU AMAN!");
        }
        $this->line("====================================================================");
    }
}