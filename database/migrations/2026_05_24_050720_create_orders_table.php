<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('po_batch_id')->nullable()->constrained('po_batches')->nullOnDelete();
            
            $table->string('order_number')->unique(); // Nomor Nota (Contoh: ORD-0001)
            $table->date('order_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            
            // Status Alur Barang
            $table->enum('status', ['draft', 'processing', 'completed', 'canceled'])->default('draft');
            
            // Status Pembayaran (Untuk Ledger Nanti)
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
