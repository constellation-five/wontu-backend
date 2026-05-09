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
        Schema::create('buyer_items', function (Blueprint $table) {
            $table->unsignedBigInteger('offer_buyer_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->primary(['offer_buyer_id', 'item_id']);
            $table->foreign('offer_buyer_id')->references('offer_buyer_id')->on('offer_buyers')->onDelete('cascade');
            $table->foreign('item_id')->references('item_id')->on('items')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_items');
    }
};
