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
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('sku');
            $table->integer('quantity');
            $table->integer('unit_price_cents'); // Price per unit in cents
            $table->integer('subtotal_cents'); // quantity * unit_price_cents
            $table->timestamps();
            
            $table->index(['order_id']);
            $table->index(['sku']);
            
            $table->foreign('order_id')->references('id')->on('orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
