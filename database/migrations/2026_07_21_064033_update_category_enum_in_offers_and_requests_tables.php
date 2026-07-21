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
        // First change to string (VARCHAR)
        DB::statement("ALTER TABLE offers MODIFY category VARCHAR(64) NOT NULL");
        DB::statement("ALTER TABLE requests MODIFY category VARCHAR(64) NOT NULL");

        // Migrate existing data
        DB::table('offers')->where('category', 'food')->update(['category' => 'Food']);
        DB::table('offers')->where('category', 'other')->update(['category' => 'Others']);

        DB::table('requests')->where('category', 'food')->update(['category' => 'Food']);
        DB::table('requests')->where('category', 'other')->update(['category' => 'Others']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert data
        DB::table('offers')->where('category', 'Food')->update(['category' => 'food']);
        DB::table('offers')->where('category', 'Others')->update(['category' => 'other']);
        // Default any new categories back to 'other' so enum constraint doesn't fail
        DB::table('offers')->whereNotIn('category', ['food', 'other'])->update(['category' => 'other']);

        DB::table('requests')->where('category', 'Food')->update(['category' => 'food']);
        DB::table('requests')->where('category', 'Others')->update(['category' => 'other']);
        DB::table('requests')->whereNotIn('category', ['food', 'other'])->update(['category' => 'other']);

        // Revert to enum
        DB::statement("ALTER TABLE offers MODIFY category ENUM('food', 'other') NOT NULL");
        DB::statement("ALTER TABLE requests MODIFY category ENUM('food', 'other') NOT NULL");
    }
};
