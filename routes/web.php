<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;
use App\Models\Wallet;
use App\Models\Ledger;
use App\Models\FinanceCategory;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/audit-keuangan-pos', function () {
    $businessId = 1; 
    $report = [];

    $wallets = Wallet::where('business_id', $businessId)->get();
    foreach ($wallets as $wallet) {
        $totalLedgerIn = Ledger::where('business_id', $businessId)->where('wallet_id', $wallet->id)->where('type', 'in')->sum('amount');
        $totalLedgerOut = Ledger::where('business_id', $businessId)->where('wallet_id', $wallet->id)->where('type', 'out')->sum('amount');
        $ekspektasiSaldo = $totalLedgerIn - $totalLedgerOut;

        if ((float)$wallet->balance != (float)$ekspektasiSaldo) {
            $report['bocor_kas'][] = [
                'nama_dompet' => $wallet->name,
                'saldo_kolom_wallet' => (float)$wallet->balance,
                'saldo_hitungan_ledger' => (float)$ekspektasiSaldo,
                'selisih_bocor' => (float)($wallet->balance - $ekspektasiSaldo),
                'analisis' => "⚠️ Ada fitur/query yang memotong/menambah saldo dompet secara langsung tanpa mencatatkan slip Ledger!"
            ];
        }
    }

    $modalCategory = FinanceCategory::withoutGlobalScopes()->where('code', 'EQ_MODAL')->first();
    if ($modalCategory) {
        $nominalModalLedger = Ledger::where('business_id', $businessId)
            ->where('finance_category_id', $modalCategory->id)
            ->sum('amount');

        if ($nominalModalLedger != 36000000) {
            $report['bocor_modal'] = [
                'nominal_seharusnya' => 36000000,
                'nominal_tercatat_di_ledger' => (float)$nominalModalLedger,
                'selisih_hilang' => 36000000 - $nominalModalLedger,
                'analisis' => "⚠️ Saldo kas diisi 36 juta, tapi slip bukti Ledger kategori 'EQ_MODAL' nominalnya salah atau tidak terbuat!"
            ];
        }
    } else {
        $report['bocor_modal'] = "⚠️ Gagal Audit: Kategori COA dengan kode 'EQ_MODAL' tidak ditemukan di database.";
    }

    $opnameClass = 'App\Models\StockOpname'; 
    
    if (class_exists($opnameClass)) {
        $allOpnames = $opnameClass::where('business_id', $businessId)->get();
        
        $catLossId = FinanceCategory::withoutGlobalScopes()->where('code', 'EXP_LOSS')->first()?->id;
        $catGainId = FinanceCategory::withoutGlobalScopes()->where('code', 'INC_GAIN')->first()?->id;

        foreach ($allOpnames as $opname) {
            $seharusnyaKeuntungan = 0;
            $seharusnyaKerugian = 0;

            foreach ($opname->items as $item) {
                $val = (float)$item->adjustment_value;
                if ($val > 0) $seharusnyaKeuntungan += $val;
                if ($val < 0) $seharusnyaKerugian += abs($val);
            }

            $realitaLedgerLoss = Ledger::where('reference_type', $opnameClass)
                ->where('reference_id', $opname->id)
                ->where('finance_category_id', $catLossId)
                ->where('type', 'out')
                ->sum('amount');

            $realitaLedgerGain = Ledger::where('reference_type', $opnameClass)
                ->where('reference_id', $opname->id)
                ->where('finance_category_id', $catGainId)
                ->where('type', 'in')
                ->sum('amount');

            $isPincangLoss = abs($seharusnyaKerugian - $realitaLedgerLoss) > 0.01;
            $isPincangGain = abs($seharusnyaKeuntungan - $realitaLedgerGain) > 0.01;

            if ($isPincangLoss || $isPincangGain) {
                $report['bocor_stock_opname'][] = [
                    'opname_id' => $opname->id,
                    'tanggal_opname' => $opname->opname_date ?? $opname->created_at,
                    'notes' => $opname->notes ?? '-',
                    'rincian_error' => [
                        'keuntungan_seharusnya' => $seharusnyaKeuntungan,
                        'keuntungan_tercatat_ledger' => (float)$realitaLedgerGain,
                        'kerugian_seharusnya' => $seharusnyaKerugian,
                        'kerugian_tercatat_ledger' => (float)$realitaLedgerLoss,
                    ],
                    'analisis' => "❌ PINCANG! Nilai penyesuaian fisik barang di gudang TIDAK SAMA dengan nilai slip akuntansi Ledger. Kemungkinan besar saat klik simpan SO, kategori COA 'EXP_LOSS' atau 'INC_GAIN' lu sempat terhapus/berstatus tidak aktif!"
                ];
            }
        }
    } else {
        $report['bocor_stock_opname'] = "💡 Info: Model StockOpname tidak terdeteksi, melewati pemeriksaan SO.";
    }

    return response()->json([
        'status' => 'Audit Selesai',
        'datetime_audit' => now()->format('Y-m-d H:i:s'),
        'hasil_temuan_bocor' => !empty($report) ? $report : 'Kondisi 100% Aman Sempurna (Tidak ada kebocoran logika antar tabel)',
        'baca_petunjuk' => 'Jika temuan bocor mengeluarkan list "bocor_stock_opname", silakan jalankan perbaikan manual nominal ledger atau jalankan seeder COA sistem agar nilainya klop Rp 0,00.'
    ]);
});
Route::get('/invoice/{order_number}', [InvoiceController::class, 'show'])->name('invoice.show');
Route::get('/invoice/{order_number}/print', [InvoiceController::class, 'print'])->name('invoice.print');
Route::get('/invoice/{order_number}/download', [InvoiceController::class, 'download'])->name('invoice.download');
Route::get('/invoice/{order_number}/resi', function ($orderNumber) {
    $order = \App\Models\Order::with(['business', 'customer', 'orderItems.product', 'delivery.courier'])
        ->where('order_number', $orderNumber)
        ->firstOrFail();
        
    return view('public.invoices.resi-thermal', compact('order'));
})->name('invoice.resi');
Route::get('/invoice/{order_number}/kwitansi', [InvoiceController::class, 'printKwitansi'])
    ->name('invoice.kwitansi');
Route::livewire('/portal/{slug}', 'pages::public.customer-portal')->name('customer.portal');
Route::livewire('/courier/{tracking_code}', 'pages::courier.delivery-proof')->name('courier.proof');
Route::get('/invoice/{order_number}/preview-batch', [InvoiceController::class, 'previewBatch'])
    ->name('invoice.preview-batch');

Route::get('/invoice/{order_number}/print-batch', [InvoiceController::class, 'printBatch'])
    ->name('invoice.print-batch');
Route::middleware(['auth'])->group(function () {
    
    Route::prefix('pos')->group(function () {
        Route::livewire('/penjualan', 'pages::pos.penjualan')->name('pos.penjualan'); 
        
        Route::livewire('/pembelian', 'pages::pos.pembelian')->name('pos.pembelian');
        Route::livewire('/keuangan', 'pages::pos.keuangan')->name('pos.keuangan');
        Route::livewire('/po', 'pages::pos.manajemen-p-o')->name('pos.po');
    });
});