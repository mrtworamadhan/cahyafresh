<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Customer;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Membuat kolom slug yang unik
            $table->string('slug')->unique()->nullable()->after('phone');
        });

        // Mengisi slug otomatis untuk data pelanggan lama yang sudah ada di database
        $customers = Customer::all();
        foreach ($customers as $customer) {
            $customer->update(['slug' => strtoupper(Str::random(8))]);
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};