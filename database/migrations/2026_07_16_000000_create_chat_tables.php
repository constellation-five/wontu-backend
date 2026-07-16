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
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['private', 'offer_group']);
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->foreign('offer_id')->references('offer_id')->on('offers')->onDelete('cascade');
            $table->timestamps();

            // Only one group-chat conversation per offer.
            $table->unique('offer_id');
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->uuid('conversation_id');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->enum('role', ['owner', 'member'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreignUuid('sender_id')->nullable()->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignUuid('target_user_id')->nullable()->constrained('users', 'user_id')->onDelete('cascade');
            $table->text('body')->nullable();
            $table->string('image_url', 256)->nullable();
            $table->enum('type', ['text', 'system'])->default('text');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
