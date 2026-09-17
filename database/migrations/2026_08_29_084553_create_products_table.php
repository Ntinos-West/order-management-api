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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('vat_code');
            $table->string('code')->unique();
            $table->string('name');
            $table->float('price');
            $table->float('discount');
            $table->boolean('not_active');
            $table->timestamps();
            
            $table->foreign('vat_code')->references('code')->on('vats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
