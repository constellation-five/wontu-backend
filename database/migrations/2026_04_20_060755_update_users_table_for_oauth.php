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
        $gipk = collect(DB::select("SHOW VARIABLES LIKE 'sql_generate_invisible_primary_key'"))->isNotEmpty();

        if ($gipk) {
            DB::statement('SET SESSION sql_generate_invisible_primary_key = OFF');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('id')->change();
            $table->dropPrimary('id');

            $table->uuid('user_id')->primary()->first();
            $table->string('google_id')->nullable()->unique()->after('user_id');
            $table->string('avatar')->nullable();
            $table->string('username', 31)->unique()->after('email');

            $table->dropColumn(['id', 'email_verified_at', 'password']);
        });

        if ($gipk) {
            DB::statement('SET SESSION sql_generate_invisible_primary_key = ON');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $gipk = collect(DB::select("SHOW VARIABLES LIKE 'sql_generate_invisible_primary_key'"))->isNotEmpty();

        if ($gipk) {
            DB::statement('SET SESSION sql_generate_invisible_primary_key = OFF');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary(['user_id']);
            $table->id()->first(); // Re-adds auto-incrementing PK

            $table->dropColumn(['user_id', 'google_id', 'avatar', 'username']);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
        });

        if ($gipk) {
            DB::statement('SET SESSION sql_generate_invisible_primary_key = ON');
        }
    }
};
