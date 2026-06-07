<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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