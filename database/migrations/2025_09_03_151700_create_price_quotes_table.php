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
        Schema::create('price_quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('quote_id')->unique();
            $table->string('sku');
            $table->integer('quantity');
            $table->integer('unit_price_cents'); // Quoted price in cents
            $table->integer('total_price_cents'); // Total = unit_price * quantity
            $table->integer('basis_spot_cents'); // Spot price at quote time
            $table->unsignedBigInteger('basis_version'); // spot_prices.id reference
            $table->integer('tolerance_bps')->default(50); // Tolerance in basis points
            $table->timestamp('quote_expires_at'); // Quote expiry (now + 5 minutes)
            $table->timestamps();

            $table->index(['quote_id']);
            $table->index(['sku', 'created_at']);
            $table->index(['quote_expires_at']);

            $table->foreign('basis_version')->references('id')->on('spot_prices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_quotes');
    }
};
