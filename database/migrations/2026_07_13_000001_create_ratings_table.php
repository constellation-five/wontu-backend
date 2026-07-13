<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->uuid('rating_id')->primary();
            $table->uuid('rater_id'); // user yang ngasih rating
            $table->uuid('rated_user_id'); // user yang dikasih rating
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->text('review')->nullable();
            $table->timestamps();

            $table->foreign('rater_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('rated_user_id')->references('user_id')->on('users')->onDelete('cascade');
            
            // Satu user cuma bisa kasih rating ke user lain satu kali
            $table->unique(['rater_id', 'rated_user_id']);
            
            $table->index('rated_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
