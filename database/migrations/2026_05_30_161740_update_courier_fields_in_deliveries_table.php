<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            // Hapus kolom manual yang lama
            $table->dropColumn(['courier_type', 'courier_name', 'vehicle_plate']);
            
            // Tambahkan relasi ke tabel Master Kurir
            $table->foreignId('courier_id')->nullable()->after('order_id')->constrained('couriers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['courier_id']);
            $table->dropColumn('courier_id');
            
            // Kembalikan seperti semula jika di-rollback
            $table->enum('courier_type', ['internal', 'external'])->default('internal');
            $table->string('courier_name'); 
            $table->string('vehicle_plate')->nullable();
        });
    }
};