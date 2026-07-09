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
        // Replaces offer_user: same purpose (tracking who has bought into an
        // offer), but now also tracks the order's verification/status instead
        // of being a plain membership pivot.
        Schema::dropIfExists('offer_user');

        Schema::create('offer_buyers', function (Blueprint $table) {
            $table->id('offer_buyer_id');
            $table->unsignedBigInteger('offer_id');
            $table->foreign('offer_id')->references('offer_id')->on('offers')->onDelete('cascade');
            $table->foreignUuid('buyer_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->boolean('is_verified')->default(false);
            $table->string('payment_proof_url', 256)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed'])->default('pending');
            $table->timestamps();

            // A buyer can only have one (active) order per offer.
            $table->unique(['offer_id', 'buyer_id']);
        });

        Schema::create('buyer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_buyer_id')->constrained('offer_buyers', 'offer_buyer_id')->onDelete('cascade');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('item_id')->on('items')->onDelete('cascade');
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['offer_buyer_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_items');
        Schema::dropIfExists('offer_buyers');

        Schema::create('offer_user', function (Blueprint $table) {
            $table->unsignedBigInteger('offer_id');
            $table->foreignUuid('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreign('offer_id')->references('offer_id')->on('offers')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['offer_id', 'user_id']);
        });
    }
};
