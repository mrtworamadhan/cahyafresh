<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('product_units', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        
        $table->string('unit_name'); // misal: Peti
        $table->integer('conversion_value'); // misal: 13 (artinya 1 Peti = 13 base_unit)
        
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('product_units');
}
};
