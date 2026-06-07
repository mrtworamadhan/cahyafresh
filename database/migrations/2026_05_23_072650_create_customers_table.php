<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            // Wajib untuk Tenancy / Multi-Business
            $table->foreignId('business_id')->constrained()->cascadeOnDelete(); 
            
            // Data dasar pelanggan
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            
            // Pondasi sistem komisi/referral
            $table->string('referral_code')->unique(); // Kode unik milik dia (Misal: BOS123)
            // Relasi ke tabel ini sendiri (Siapa yang ngajak dia)
            $table->foreignId('referred_by_id')->nullable()->constrained('customers')->nullOnDelete(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
