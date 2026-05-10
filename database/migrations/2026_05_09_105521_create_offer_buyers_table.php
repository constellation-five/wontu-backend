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
        Schema::create('offer_buyers', function (Blueprint $table) {
            $table->id('offer_buyer_id');
            $table->unsignedBigInteger('offer_id');
            $table->foreign('offer_id')->references('offer_id')->on('offers')->onDelete('cascade');
            $table->foreignUuid('buyer_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['pending', 'confirmed', 'completed'])->default('pending');
            $table->string('payment_proof_url', 256)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_buyers');
    }
};
