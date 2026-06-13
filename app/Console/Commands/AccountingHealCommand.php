<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\FinanceCategory;
use Illuminate\Support\Facades\DB;

class AccountingHealCommand extends Command
{
    protected $signature = 'accounting:heal';
    protected $description = 'Menyembuhkan otomatis tabel master agar sinkron dengan riwayat ledger (Zero Adjustment)';

    public function handle()
    {
        $this->info('======================================================');
        $this->info('🛠️ MEMULAI PENYEMBUHAN & SINKRONISASI MASSAL...');
        $this->info('======================================================');

        DB::transaction(function () {

            // ------------------------------------------------------------------
            // 1. SEMBUHKAN SALDO DOMPET/BANK
            // ------------------------------------------------------------------
            $this->comment('1. Menyinkronkan Saldo Tabel Wallet dengan Riwayat Ledger...');
            $wallets = Wallet::all();
            foreach ($wallets as $wallet) {
                $cashIn = Ledger::where('wallet_id', $wallet->id)->where('type', 'in')->sum('amount');
                $cashOut = Ledger::where('wallet_id', $wallet->id)->where('type', 'out')->sum('amount');
                $expectedBalance = $cashIn - $cashOut;

                if ((float)$wallet->balance != $expectedBalance) {
                    $wallet->update(['balance' => $expectedBalance]);
                    $this->info("   ✅ Saldo dompet '{$wallet->name}' berhasil disamakan menjadi Rp " . number_format($expectedBalance, 0, ',', '.'));
                }
            }

            // ------------------------------------------------------------------
            // 2. SEMBUHKAN SALDO KOMISI AGEN
            // ------------------------------------------------------------------
            $this->comment("\n" . '2. Menyinkronkan Saldo Komisi Agen dengan Riwayat Ledger...');
            $catOpCommission = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_COMMISSION')->first()?->id;
            $catLiaCommPaid  = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_COMMISSION_PAID')->first()?->id;

            if ($catOpCommission && $catLiaCommPaid) {
                $customers = Customer::all();
                foreach ($customers as $customer) {
                    $totalEarned = Ledger::where('contact_id', $customer->id)
                        ->where('contact_type', Customer::class)
                        ->where('finance_category_id', $catOpCommission)
                        ->sum('amount');

                    $totalWithdrawn = Ledger::where('contact_id', $customer->id)
                        ->where('contact_type', Customer::class)
                        ->where('finance_category_id', $catLiaCommPaid)
                        ->sum('amount');

                    $expectedCommBalance = max(0, $totalEarned - $totalWithdrawn);

                    if ((float)$customer->commission_balance != $expectedCommBalance) {
                        $customer->update(['commission_balance' => $expectedCommBalance]);
                        $this->info("   ✅ Saldo komisi agen '{$customer->name}' berhasil disamakan menjadi Rp " . number_format($expectedCommBalance, 0, ',', '.'));
                    }
                }
            }

            // ------------------------------------------------------------------
            // 3. SEMBUHKAN SIKLUS HUTANG ONGKIR KURIR
            // ------------------------------------------------------------------
            $this->comment("\n" . '3. Menyembuhkan Selisih Hutang Ongkir Surat Jalan Kurir...');
            $catOpShipping  = FinanceCategory::withoutGlobalScopes()->where('code', 'OP_SHIPPING')->first()?->id;
            $catLiaShipPaid = FinanceCategory::withoutGlobalScopes()->where('code', 'LIA_SHIPPING_PAID')->first()?->id;

            if ($catOpShipping && $catLiaShipPaid) {
                $totalBebanKurir = Ledger::where('finance_category_id', $catOpShipping)->sum('amount');
                $totalRilisKurir = Ledger::where('finance_category_id', $catLiaShipPaid)->sum('amount');
                $expectedHutangOngkir = $totalBebanKurir - $totalRilisKurir;

                // Hasil Audit Lu: Hutang riil manifest = 0, tapi ledger mencatat -175.000
                // Artinya ada kelebihan rilis duit kas keluar ke kurir tanpa dasar manifest surat jalan sebesar 175rb
                if ($expectedHutangOngkir < 0) {
                    $selisihGhaib = abs($expectedHutangOngkir);
                    
                    // Kita lahirkan Jurnal Penyesuaian Pengakuan Beban Kurir non-tunai (wallet_id null)
                    // Agar nominal pengakuan beban naik 175rb mengimbangi kas keluar riilnya
                    Ledger::create([
                        'business_id' => Ledger::where('finance_category_id', $catLiaShipPaid)->first()?->business_id ?? 1,
                        'wallet_id' => null, 
                        'finance_category_id' => $catOpShipping,
                        'transaction_date' => now(),
                        'description' => "Jurnal Penyesuaian Otomatis: Sinkronisasi Kas Keluar vs Manifest Kurir (Auto-Heal)",
                        'type' => 'out',
                        'amount' => $selisihGhaib,
                    ]);

                    $this->info("   ✅ Berhasil membuat 1 baris Ledger Pengakuan Beban Ongkir baru sebesar Rp " . number_format($selisihGhaib, 0, ',', '.') . " untuk menyerap minus ghaib.");
                }
            }
        });

        $this->line("\n======================================================");
        $this->info('🎉 PROSES PENYEMBUHAN SELESAI SECARA SEMPURNA!');
        $this->info('   Silakan refresh halaman LaporanKeuangan.php Anda sekarang.');
        $this->line("======================================================");
    }
}