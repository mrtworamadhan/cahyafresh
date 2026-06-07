<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('system_stock'); // Stok di sistem sebelum diubah
            $table->integer('actual_stock'); // Stok fisik hitungan manusia
            $table->integer('difference'); // Selisih (actual - system). Kalau minus berarti hilang.
            $table->decimal('hpp', 15, 2)->default(0); // Harga modal per item saat opname
            $table->decimal('adjustment_value', 15, 2)->default(0); // difference * hpp (Nilai Rp barang yang hilang/ketemu)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};