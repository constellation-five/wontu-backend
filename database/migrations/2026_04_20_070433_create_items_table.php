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
        Schema::create('items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('offer_id')->constrained('offers', 'offer_id')->onDelete('cascade');
            $table->string('item_name', 64);
            $table->decimal('item_price', 10, 3);
            $table->string('item_url', 256)->nullable();
            $table->integer('current_slot')->default(0);
            $table->integer('slot')->default(0);
            $table->string('image_url', 256)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
