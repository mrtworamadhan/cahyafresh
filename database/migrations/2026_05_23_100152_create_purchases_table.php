<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('purchases', function (Blueprint $table) {
        $table->id();
        $table->foreignId('business_id')->constrained()->cascadeOnDelete();
        $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
        $table->string('invoice_number'); // Nomor Nota
        $table->date('purchase_date'); // Tanggal Belanja
        $table->decimal('total_amount', 15, 2)->default(0); // Total Belanja
        $table->enum('status', ['partial','paid', 'unpaid'])->default('unpaid'); // Lunas atau Hutang
        
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('purchases');
}
};
