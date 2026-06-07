<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Menambahkan kolom tepat setelah status pembayaran
            $table->enum('delivery_type', ['pickup', 'delivery'])->default('pickup')->after('payment_status');
            $table->decimal('shipping_fee_billed', 15, 2)->default(0)->after('delivery_type'); // Ongkir ke konsumen
            $table->decimal('shipping_cost_actual', 15, 2)->default(0)->after('shipping_fee_billed'); // Ongkir asli kita
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'shipping_fee_billed', 'shipping_cost_actual']);
        });
    }
};