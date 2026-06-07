<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('period_name'); // Contoh: "Januari 2024"
            $table->date('closing_date'); // Tanggal jepret tutup buku
            $table->json('snapshot_data'); // Disimpan dalam format JSON agar strukturnya abadi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_closings');
    }
};