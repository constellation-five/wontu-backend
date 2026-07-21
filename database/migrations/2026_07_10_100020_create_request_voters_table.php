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
        Schema::create('request_voters', function (Blueprint $table) {
            $table->unsignedBigInteger('request_id');
            $table->uuid('user_id');

            $table->primary(['request_id', 'user_id']);

            $table->foreign('request_id')->references('request_id')->on('requests')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_voters');
    }
};
