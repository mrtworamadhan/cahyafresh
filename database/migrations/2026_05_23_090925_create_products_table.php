<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            
            $table->string('sku')->nullable(); // Kode Barang
            $table->string('name'); // Nama Barang, misal: Pisang Ambon
            $table->text('description')->nullable();
            
            $table->string('base_unit'); // Satuan Terkecil, misal: Kg atau Pcs
            $table->decimal('base_price', 15, 2); // Harga Modal / HPP
            $table->decimal('selling_price', 15, 2); // Harga Jual Default
            
            $table->integer('stock')->default(0); // Stok fisik berdasarkan base_unit
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
