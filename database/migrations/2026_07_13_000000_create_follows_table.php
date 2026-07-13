<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->uuid('follower_id');
            $table->uuid('following_id');
            $table->timestamps();

            $table->primary(['follower_id', 'following_id']);
            
            $table->foreign('follower_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('following_id')->references('user_id')->on('users')->onDelete('cascade');
            
            $table->index('follower_id');
            $table->index('following_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
