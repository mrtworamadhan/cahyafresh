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
    protected $description = 'Menyembuhkan otomatis tabel master dan menyuntikkan jurnal ghaib akibat kegagalan Stock Opname';

    public function handle()
    {
        $this->info('======================================================');
        $this->info('🛠️ MEMULAI PENYEMBUHAN & SINKRONISASI MASSAL...');
        $this->info('======================================================');

        $businessId = 1; // Sesuaikan dengan id bisnis utama lu

        DB::transaction(function () use ($businessId) {

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

                if ($expectedHutangOngkir < 0) {
                    $selisihGhaib = abs($expectedHutangOngkir);
                    
                    Ledger::create([
                        'business_id' => Ledger::where('finance_category_id', $catLiaShipPaid)->first()?->business_id ?? $businessId,
                        'wallet_id' => null, 
                        'finance_category_id' => $catOpShipping,
                        'transaction_date' => now(),
                        'description' => "Jurnal Penyesuaian Otomatis: Sinkronisasi Kas Keluar vs Manifest Kurir (Auto-Heal)",
                        'type' => 'out',
                        'amount' => $selisihGhaib,
                    ]);

                    $this->info("   ✅ Berhasil membuat 1 baris Ledger Pengakuan Beban Ongkir baru sebesar Rp " . number_format($selisihGhaib, 0, ',', '.'));
                }
            }

            // ------------------------------------------------------------------
            // UPDATED - 4. BACKFILL JURNAL SAKRAL STOCK OPNAME (ANTI-PINCANG)
            // ------------------------------------------------------------------
            $this->comment("\n" . '4. Memeriksa & Menyembuhkan Jurnal Ghaib Hasil Stock Opname (SO)...');
            $opnameClass = 'App\Models\StockOpname'; // Sesuaikan namespace model SO lu
            
            if (class_exists($opnameClass)) {
                $catLoss = FinanceCategory::withoutGlobalScopes()->where('code', 'EXP_LOSS')->first();
                $catGain = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_GAIN')->first();

                if ($catLoss && $catGain) {
                    $allOpnames = $opnameClass::with('items')->get();
                    $healedSoCount = 0;

                    foreach ($allOpnames as $opname) {
                        $seharusnyaKeuntungan = 0;
                        $seharusnyaKerugian = 0;

                        foreach ($opname->items as $item) {
                            $val = (float)$item->adjustment_value;
                            if ($val > 0) $seharusnyaKeuntungan += $val;
                            if ($val < 0) $seharusnyaKerugian += abs($val);
                        }

                        // A. Suntik Balik Beban Kerugian Barang Hilang (EXP_LOSS)
                        if ($seharusnyaKerugian > 0) {
                            $existLoss = Ledger::where('reference_type', $opnameClass)
                                ->where('reference_id', $opname->id)
                                ->where('finance_category_id', $catLoss->id)
                                ->exists();
                            
                            if (!$existLoss) {
                                Ledger::create([
                                    'business_id' => $opname->business_id ?? $businessId,
                                    'wallet_id' => null, // Non-tunai
                                    'finance_category_id' => $catLoss->id,
                                    'transaction_date' => $opname->opname_date ?? $opname->created_at ?? now(),
                                    'description' => "Beban Kerugian Stok (Opname #{$opname->id}) - Auto Healed",
                                    'type' => 'out',
                                    'amount' => $seharusnyaKerugian,
                                    'reference_type' => $opnameClass,
                                    'reference_id' => $opname->id,
                                ]);
                                $healedSoCount++;
                            }
                        }

                        // B. Suntik Balik Pendapatan Selisih Lebih Barang (INC_GAIN)
                        if ($seharusnyaKeuntungan > 0) {
                            $existGain = Ledger::where('reference_type', $opnameClass)
                                ->where('reference_id', $opname->id)
                                ->where('finance_category_id', $catGain->id)
                                ->exists();

                            if (!$existGain) {
                                Ledger::create([
                                    'business_id' => $opname->business_id ?? $businessId,
                                    'wallet_id' => null, // Non-tunai
                                    'finance_category_id' => $catGain->id,
                                    'transaction_date' => $opname->opname_date ?? $opname->created_at ?? now(),
                                    'description' => "Pendapatan Penyesuaian Stok Ditemukan (Opname #{$opname->id}) - Auto Healed",
                                    'type' => 'in',
                                    'amount' => $seharusnyaKeuntungan,
                                    'reference_type' => $opnameClass,
                                    'reference_id' => $opname->id,
                                ]);
                                $healedSoCount++;
                            }
                        }
                    }
                    $this->info("   ✅ Sukses melunasi {$healedSoCount} slip jurnal penyesuaian SO ghaib masa lalu!");
                }
            }
        });

        $this->line("\n======================================================");
        $this->info('🎉 PROSES PENYEMBUHAN SELESAI SECARA SEMPURNA!');
        $this->info('   Silakan refresh halaman LaporanKeuangan.php Anda sekarang.');
        $this->line("======================================================");
    }
}