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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->uuid('user_id')->primary()->first();
            $table->string('google_id')->nullable()->unique()->after('user_id');
            $table->string('avatar')->nullable();
            $table->string('username', 31)->unique()->after('email');
            $table->dropColumn(['email_verified_at', 'password']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->id()->first();
            $table->dropColumn('google_id');
            $table->dropColumn('avatar');
            $table->dropColumn('username');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
        });
    }
};
