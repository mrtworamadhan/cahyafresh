<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('ledgers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('business_id')->constrained()->cascadeOnDelete();
        $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
        
        $table->date('transaction_date');
        $table->string('description'); 
        
        $table->enum('type', ['in', 'out']); 
        $table->decimal('amount', 15, 2);
        
        $table->nullableMorphs('contact'); 
        
        $table->nullableMorphs('reference');
        
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('ledgers');
}
};