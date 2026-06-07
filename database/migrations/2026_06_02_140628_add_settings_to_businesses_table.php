<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration untuk menambah kolom.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('theme_color')->default('#f59e0b')->after('logo'); // Default warna tema
            $table->string('invoice_footer_text')->nullable()->after('theme_color');
            $table->boolean('is_tax_enabled')->default(false)->after('invoice_footer_text');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('is_tax_enabled');
        });
    }

    /**
     * Kembalikan (hapus) kolom jika migration di-rollback.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'theme_color', 
                'invoice_footer_text', 
                'is_tax_enabled', 
                'tax_rate'
            ]);
        });
    }
};