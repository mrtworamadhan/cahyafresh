<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            // nullable() agar Kategori Sistem bisa jadi Global (milik semua toko)
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Untuk Sub-Kategori (Menginduk ke Kategori Utama)
            $table->foreignId('parent_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            
            $table->string('code')->nullable()->unique(); // Contoh: 'EQ_MODAL', 'EXP_GAJI' (Kunci untuk PHP)
            $table->string('name');
            $table->string('type'); // in (Pemasukan), out (Pengeluaran), equity (Modal/Prive)
            $table->text('description')->nullable();
            
            // KUNCI GEMBOK: Jika true, user tidak bisa hapus/edit kategori ini
            $table->boolean('is_system')->default(false); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_categories');
    }
};