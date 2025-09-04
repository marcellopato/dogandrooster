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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id')->unique();
            $table->string('idempotency_key')->unique();
            $table->uuid('quote_id');
            $table->enum('status', ['pending', 'authorized', 'captured', 'failed'])
                ->default('pending');
            $table->integer('total_cents'); // Total order amount in cents
            $table->string('payment_intent_id')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['status', 'created_at']);
            $table->index(['idempotency_key']);
            $table->index(['payment_intent_id']);

            $table->foreign('quote_id')->references('quote_id')->on('price_quotes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
