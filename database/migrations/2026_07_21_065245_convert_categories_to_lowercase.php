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
        // offers
        DB::statement("UPDATE offers SET category = 'food' WHERE category = 'Food'");
        DB::statement("UPDATE offers SET category = 'electronics' WHERE category = 'Electronics'");
        DB::statement("UPDATE offers SET category = 'fashion' WHERE category = 'Fashion'");
        DB::statement("UPDATE offers SET category = 'home' WHERE category = 'Home'");
        DB::statement("UPDATE offers SET category = 'beauty' WHERE category = 'Beauty'");
        DB::statement("UPDATE offers SET category = 'gaming' WHERE category = 'Gaming'");
        DB::statement("UPDATE offers SET category = 'sports' WHERE category = 'Sports'");
        DB::statement("UPDATE offers SET category = 'other' WHERE category = 'Others'");

        // requests
        DB::statement("UPDATE requests SET category = 'food' WHERE category = 'Food'");
        DB::statement("UPDATE requests SET category = 'electronics' WHERE category = 'Electronics'");
        DB::statement("UPDATE requests SET category = 'fashion' WHERE category = 'Fashion'");
        DB::statement("UPDATE requests SET category = 'home' WHERE category = 'Home'");
        DB::statement("UPDATE requests SET category = 'beauty' WHERE category = 'Beauty'");
        DB::statement("UPDATE requests SET category = 'gaming' WHERE category = 'Gaming'");
        DB::statement("UPDATE requests SET category = 'sports' WHERE category = 'Sports'");
        DB::statement("UPDATE requests SET category = 'other' WHERE category = 'Others'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
