<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('deposit_balance', 15, 2)->default(0)->after('phone');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('deposit_balance', 15, 2)->default(0)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('deposit_balance');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('deposit_balance');
        });
    }
};