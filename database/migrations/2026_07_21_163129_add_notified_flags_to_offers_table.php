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
            $table->boolean('notified_sold_out_early')->default(false)->after('closed_at');
            $table->boolean('notified_closing_reached')->default(false)->after('notified_sold_out_early');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['notified_sold_out_early', 'notified_closing_reached']);
        });
    }
};
