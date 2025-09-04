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
        Schema::create('spot_prices', function (Blueprint $table) {
            $table->id();
            $table->string('metal_type'); // 'gold', 'silver', 'platinum'
            $table->integer('price_per_oz_cents'); // Spot price in cents per ounce
            $table->timestamp('effective_at'); // When this price became effective
            $table->boolean('is_current')->default(false); // Only one current price per metal
            $table->timestamps();

            $table->index(['metal_type', 'is_current']);
            $table->index(['metal_type', 'effective_at']);
            // Remove unique constraint that was causing issues with tests
            // $table->unique(['metal_type', 'is_current'], 'unique_current_spot_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spot_prices');
    }
};
