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
        Schema::table('offers', function (Blueprint $table) {
            // Set once every buyer's payment on this offer has been
            // confirmed by the seller — shown as its own step in the
            // Manage Offer timeline, distinct from arrived_at/closed_at.
            $table->dateTime('payments_confirmed_at')->nullable()->after('arrived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('payments_confirmed_at');
        });
    }
};
