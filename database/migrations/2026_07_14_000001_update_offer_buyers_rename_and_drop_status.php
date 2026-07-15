<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * doctrine/dbal is not installed in this project, so column renames use
     * raw `ALTER TABLE ... CHANGE` statements instead of
     * Blueprint::renameColumn() (which requires doctrine/dbal).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE offer_buyers CHANGE is_verified is_confirmed TINYINT(1) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE offer_buyers CHANGE verified_at confirmed_at DATETIME NULL DEFAULT NULL');

        Schema::table('offer_buyers', function ($table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offer_buyers', function ($table) {
            $table->enum('status', ['pending', 'confirmed', 'completed'])->default('pending');
        });

        DB::statement('ALTER TABLE offer_buyers CHANGE confirmed_at verified_at DATETIME NULL DEFAULT NULL');
        DB::statement('ALTER TABLE offer_buyers CHANGE is_confirmed is_verified TINYINT(1) NOT NULL DEFAULT 0');
    }
};
