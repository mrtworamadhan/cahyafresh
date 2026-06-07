<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // 1. Tambahkan kolom business_id
            $table->foreignId('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
            
            // 2. Hapus aturan Unique lama (Karena tiap toko/bisnis boleh punya nama role "Kasir")
            $table->dropUnique('roles_name_guard_name_unique');
            
            // 3. Buat aturan Unique baru (Kombinasi nama role, guard, dan toko)
            $table->unique(['name', 'guard_name', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropUnique(['name', 'guard_name', 'business_id']);
            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
            $table->dropColumn('business_id');
        });
    }
};