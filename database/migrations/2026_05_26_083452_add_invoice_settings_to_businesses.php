<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('phone');
            $table->string('signature')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signer_title')->nullable();
            $table->string('invoice_template')->default('default'); // Pilihan: 'default' atau 'modern'
            $table->string('invoice_color')->default('#2563eb'); // Warna aksen nota
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['logo', 'signature', 'signer_name', 'signer_title', 'invoice_template', 'invoice_color']);
        });
    }
};