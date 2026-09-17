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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('product_code');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->integer('vat_code');
            $table->string('name');
            $table->float('price');
            $table->float('quantity');
            $table->float('discount');
            $table->float('total');
            $table->timestamps();
            
            $table->foreign('product_code')->references('code')->on('products');
            $table->foreign('vat_code')->references('code')->on('vats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
