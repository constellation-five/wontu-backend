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
        // closing_time/arrival_time on offers are the seller's planned
        // schedule. These new columns record when those events actually
        // happened (set by seller actions in a later change).
        Schema::table('offers', function (Blueprint $table) {
            $table->dateTime('closed_at')->nullable()->after('closing_time');
            $table->dateTime('arrived_at')->nullable()->after('arrival_time');
        });

        // Records the actual moment a buyer's order reached each payment
        // milestone. payment_submitted_at is set once the (separately built)
        // proof-of-payment upload happens; verified_at is set once the
        // seller confirms it (seller-side UI not built yet).
        Schema::table('offer_buyers', function (Blueprint $table) {
            $table->dateTime('payment_submitted_at')->nullable()->after('payment_proof_url');
            $table->dateTime('verified_at')->nullable()->after('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['closed_at', 'arrived_at']);
        });

        Schema::table('offer_buyers', function (Blueprint $table) {
            $table->dropColumn(['payment_submitted_at', 'verified_at']);
        });
    }
};
