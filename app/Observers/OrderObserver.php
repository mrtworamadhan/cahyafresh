<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockMovement; // <-- Kita ganti ke model baru

class OrderObserver
{
    /**
     * Berjalan otomatis saat data Order berhasil di-update.
     */
    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && $order->status === 'completed') {
            
            foreach ($order->orderItems as $item) {
                $product = Product::find($item->product_id);
                
                if ($product) {
                    $totalQtyOut = $item->qty_billed + $item->qty_bonus;
                    
                    $deduction = $totalQtyOut; // Default jika pakai satuan dasar
                    $unitName = 'Satuan Dasar';

                    if ($item->product_unit_id) {
                        $unit = ProductUnit::find($item->product_unit_id);
                        if ($unit && $unit->conversion_value > 0) {
                            $unitName = $unit->unit_name;
                            
                            // PERBAIKAN KRUSIAL: Harus DIKALI (*), BUKAN DIBAGI (/)
                            // Contoh: 2 Peti * 105 = 210 Pcs (Satuan Dasar)
                            $deduction = $totalQtyOut * $unit->conversion_value; 
                        }
                    }

                    // 1. Kurangi Stok Fisik (Dalam wujud Satuan Terkecil/Dasar)
                    $product->stock -= $deduction;
                    $product->save();

                    // 2. Catat di Riwayat Mutasi Stok (Kartu Stok)
                    $baseUnitName = $product->unit ?? 'Satuan Dasar';
                    
                    StockMovement::create([
                        'business_id' => $order->business_id,
                        'product_id' => $product->id,
                        'transaction_type' => 'sale', // <-- Menandakan ini dari Penjualan
                        'type' => 'out', // Keluar
                        'quantity' => $deduction,
                        'reason' => "Penjualan Nota: {$order->order_number} ({$totalQtyOut} {$unitName} = {$deduction} {$baseUnitName})",
                    ]);
                }
            }
        }
    }
}