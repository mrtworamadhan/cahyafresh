<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            // Wajib ada untuk sistem Multi-Bisnis / Multi-Cabang
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            
            $table->string('name'); // Nama Supir atau Ekspedisi (JNE, Lalamove, dll)
            $table->enum('type', ['internal', 'external'])->default('internal'); 
            $table->string('vehicle_plate')->nullable(); // Plat nomor kendaraan operasional
            $table->string('phone')->nullable(); // Nomor HP Supir (untuk dihubungi konsumen)
            $table->boolean('is_active')->default(true); // Status aktif/non-aktif
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};