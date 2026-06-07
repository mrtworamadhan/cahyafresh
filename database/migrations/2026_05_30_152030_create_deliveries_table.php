<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            
            // Tipe Ekspedisi & Kurir
            $table->enum('courier_type', ['internal', 'external'])->default('internal');
            $table->string('courier_name');
            $table->string('vehicle_plate')->nullable(); 
            
            // Biaya Pengiriman
            $table->decimal('shipping_fee_billed', 15, 2)->default(0);
            $table->decimal('shipping_cost_actual', 15, 2)->default(0);
            
            // Barcode & Tracking
            $table->string('tracking_code')->unique();
            $table->enum('status', ['pending', 'on_delivery', 'delivered', 'failed'])->default('pending');
            
            // Proof of Delivery (Paperless)
            $table->string('proof_photo_path')->nullable(); 
            $table->text('signature_data')->nullable(); 
            $table->string('receiver_name')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};