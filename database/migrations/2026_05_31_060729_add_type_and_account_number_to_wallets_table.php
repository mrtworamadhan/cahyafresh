<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Tambahkan kolom type dan account_number
            $table->enum('type', ['cash', 'bank', 'ewallet'])->default('cash')->after('name');
            $table->string('account_number')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['type', 'account_number']);
        });
    }
};