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
    
    // ⚠️ GANTI ANGKA 1 INI SESUAI ID BISNIS LU YANG MAU DI-TRACKING
    $businessId = 1; 

    $report = [];

    // 🔎 AUDIT 1: KAS vs LEDGER LOG (Cek Kebocoran Saldo Dompet)
    $wallets = Wallet::where('business_id', $businessId)->get();
    foreach ($wallets as $wallet) {
        // Hitung total mutasi uang masuk dan keluar seumur hidup khusus untuk dompet ini
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

    // 🔎 AUDIT 2: VERIFIKASI LOG SUNTIKAN MODAL AWAL 36 JUTA
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

    // Return output dalam bentuk JSON yang bersih di browser
    return response()->json([
        'status' => 'Audit Selesai',
        'hasil_temuan_bocor' => !empty($report) ? $report : 'Kondisi Aman (Tidak ada kebocoran logika antar tabel)',
        'baca_petunjuk' => 'Jika hasil_temuan_bocor bernilai aman tapi laporan tetap selisih, berarti masalahnya murni karena transaction_date modal terinput salah tanggal (Backdate) di luar jangkauan kalender laporan.'
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
Route::livewire('/portal/{slug}', 'pages::public.customer-portal')->name('customer.portal');
Route::livewire('/courier/{tracking_code}', 'pages::courier.delivery-proof')->name('courier.proof');
Route::get('/invoice/{order_number}/preview-batch', [App\Http\Controllers\InvoiceController::class, 'previewBatch'])
    ->name('invoice.preview-batch');

// Route untuk Print (Dengan perintah print otomatis - yang kita buat tadi)
Route::get('/invoice/{order_number}/print-batch', [App\Http\Controllers\InvoiceController::class, 'printBatch'])
    ->name('invoice.print-batch');
Route::middleware(['auth'])->group(function () {
    
    Route::prefix('pos')->group(function () {
        // Kita arahkan komponen lamamu ke sini sementara sebelum kamu pecah filenya
        Route::livewire('/penjualan', 'pages::pos.penjualan')->name('pos.penjualan'); 
        
        // Nanti kita buat dua komponen ini
        Route::livewire('/pembelian', 'pages::pos.pembelian')->name('pos.pembelian');
        Route::livewire('/keuangan', 'pages::pos.keuangan')->name('pos.keuangan');
        Route::livewire('/po', 'pages::pos.manajemen-p-o')->name('pos.po');
    });
});