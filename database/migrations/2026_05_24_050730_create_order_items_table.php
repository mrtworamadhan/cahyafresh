<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            // Relasi opsional ke tabel product_units (jika dia beli ukuran Peti/Dus)
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            
            // Jumlah Barang
            $table->integer('qty_billed'); // Jumlah yang dibayar (misal: 230)
            $table->integer('qty_bonus')->default(0); // Jumlah free/bonus (misal: 20)
            
            // Harga Jual dan Subtotal
            $table->decimal('unit_price', 15, 2); 
            $table->decimal('subtotal', 15, 2); // Hasil qty_billed * unit_price
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};