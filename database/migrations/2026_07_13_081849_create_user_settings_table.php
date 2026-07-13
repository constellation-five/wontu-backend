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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->uuid('setting_id')->primary();
            $table->uuid('user_id');
            
            // Notification settings - JSON untuk flexibility
            $table->json('notifications')->nullable();
            
            // Preferences
            $table->string('language', 20)->default('english');
            $table->boolean('dark_mode')->default(false);
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
            
            // Ensure one setting per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
