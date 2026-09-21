<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
    
        $table->id();
        $table->string('neighborhood');           // Nome do bairro (ex: Candeias, Centro)
        $table->decimal('customer_fee', 8, 2);    // Taxa cobrada do cliente
        $table->decimal('courier_fee', 8, 2);     // Taxa repassada ao motoboy
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
