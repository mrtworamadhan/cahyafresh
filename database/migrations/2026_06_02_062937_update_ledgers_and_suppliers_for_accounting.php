<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kategori di buku kas
        Schema::table('ledgers', function (Blueprint $table) {
            $table->foreignId('finance_category_id')->nullable()->after('wallet_id')->constrained()->nullOnDelete();
        });

        // // 2. Tambah saldo deposit di supplier
        // Schema::table('suppliers', function (Blueprint $table) {
        //     $table->decimal('deposit_balance', 15, 2)->default(0)->after('address');
        // });
    }

    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropForeign(['finance_category_id']);
            $table->dropColumn('finance_category_id');
        });

        // Schema::table('suppliers', function (Blueprint $table) {
        //     $table->dropColumn('deposit_balance');
        // });
    }
};