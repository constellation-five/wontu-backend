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
        Schema::create('offer_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->foreign('offer_id')->references('offer_id')->on('offers')->onDelete('cascade');
            $table->unsignedBigInteger('payment_method_id');
            $table->foreign('payment_method_id')->references('payment_method_id')->on('payment_methods')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['offer_id', 'payment_method_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_payment_methods');
    }
};
