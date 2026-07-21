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
        Schema::create('offers', function (Blueprint $table) {
            $table->id('offer_id');
            $table->foreignUuid('seller_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->enum('category', ['food', 'other']);
            $table->string('merchant_name', 64);
            $table->dateTime('closing_time');
            $table->dateTime('arrival_time');
            $table->boolean('has_cod_payment')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
