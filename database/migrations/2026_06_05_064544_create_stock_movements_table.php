<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type'); // Isinya nanti: 'sale', 'purchase', atau 'opname'
            $table->enum('type', ['in', 'out']); // 'in' = bertambah, 'out' = berkurang
            $table->integer('quantity'); // Jumlah barang
            $table->string('reason')->nullable(); // Keterangan detail (Contoh: "Penjualan Nota: INV-001")
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};