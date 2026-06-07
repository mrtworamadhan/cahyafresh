<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\StockMovement; // <-- Kita ganti ke model baru

class PurchaseObserver
{
    public function updated(Purchase $purchase): void
    {
        if ($purchase->wasChanged('is_stock_received') && $purchase->is_stock_received) {
            
            foreach ($purchase->purchaseItems()->with('productUnit')->get() as $item) {
                $product = Product::find($item->product_id);
                
                if ($product) {
                    $old_stock = (int) $product->stock;
                    $old_price = (float) $product->base_price;
                    
                    $multiplier = $item->productUnit ? (float)$item->productUnit->conversion_value : 1;
                    
                    $incoming_stock = (int) $item->quantity * $multiplier;
                    
                    $incoming_price_per_base = (float) $item->unit_price / $multiplier;
                    
                    $total_new_stock = $old_stock + $incoming_stock;
                    
                    if ($total_new_stock > 0) {
                        $total_old_value = $old_stock * $old_price;
                        $total_incoming_value = $incoming_stock * $incoming_price_per_base; 
                        
                        $average_price = ($total_old_value + $total_incoming_value) / $total_new_stock;
                        $product->base_price = $average_price;
                    }

                    $product->stock = $total_new_stock;
                    $product->save();

                    $satuan = $product->unit ?? 'Satuan Dasar';
                    
                    StockMovement::create([
                        'business_id' => $purchase->business_id,
                        'product_id' => $product->id,
                        'transaction_type' => 'purchase', // <-- Menandakan ini dari Pembelian
                        'type' => 'in', // Masuk
                        'quantity' => $incoming_stock,
                        'reason' => "Penerimaan Pembelian: {$purchase->invoice_number} (HPP: Rp " . number_format($incoming_price_per_base, 0, ',', '.') . "/{$satuan})",
                    ]);
                }
            }
        }
    }
}