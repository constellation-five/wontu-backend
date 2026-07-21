<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing rows to standard locale codes
        DB::table('user_settings')->where('language', 'english')->update(['language' => 'en']);
        DB::table('user_settings')->where('language', 'indonesian')->update(['language' => 'id']);

        // Change the column default to 'en'
        Schema::table('user_settings', function (Blueprint $table) {
            $table->string('language', 20)->default('en')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the column default to 'english'
        Schema::table('user_settings', function (Blueprint $table) {
            $table->string('language', 20)->default('english')->change();
        });

        // Revert the data
        DB::table('user_settings')->where('language', 'en')->update(['language' => 'english']);
        DB::table('user_settings')->where('language', 'id')->update(['language' => 'indonesian']);
    }
};
