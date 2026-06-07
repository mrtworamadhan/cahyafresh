<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('commission_amount', 15, 2)->default(0)->after('discount_amount');
            
            $table->foreignId('commission_recipient_id')->nullable()->constrained('customers')->nullOnDelete()->after('commission_amount');
            $table->string('commission_note')->nullable()->after('commission_recipient_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('commission_per_unit', 15, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['commission_recipient_id']);
            $table->dropColumn(['discount_amount', 'commission_amount', 'commission_recipient_id', 'commission_note']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('commission_per_unit');
        });
    }
};