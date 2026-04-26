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
            $table->uuid('user_id')->first();
            $table->string('google_id')->nullable()->unique()->after('user_id');
            $table->string('avatar')->nullable();
            $table->string('username', 31)->unique()->after('email');
            $table->dropColumn(['email_verified_at', 'password']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('id')->change();
            $table->dropPrimary('id');
            $table->primary('user_id');
            $table->dropColumn('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->bigInteger('id_temp')->unsigned()->nullable()->after('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary(['user_id']);
            $table->primary('id_temp');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'google_id', 'avatar', 'username']);
            $table->renameColumn('id_temp', 'id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->bigIncrements('id')->change();
        });
    }
};
