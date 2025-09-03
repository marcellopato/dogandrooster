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
            $table->string('sku')->unique(); // e.g., 'GOLD_1OZ', 'SILVER_1OZ'
            $table->string('name'); // e.g., 'Gold 1 Ounce Coin'
            $table->string('metal_type'); // 'gold', 'silver', 'platinum', etc.
            $table->decimal('weight_oz', 10, 4); // Weight in ounces (4 decimal places)
            $table->integer('premium_cents'); // Premium in cents over spot price
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['sku', 'active']);
            $table->index('metal_type');
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
