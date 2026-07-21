<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            // Add offer_id column
            $table->foreignId('offer_id')->constrained('offers', 'offer_id')->cascadeOnDelete();

            // Add new unique constraint FIRST so the rater_id FK still has an index
            $table->unique(['rater_id', 'rated_user_id', 'offer_id']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            // Now it's safe to drop the old unique constraint
            $table->dropUnique(['rater_id', 'rated_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            // Add back the old unique constraint FIRST
            $table->unique(['rater_id', 'rated_user_id']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            // Now drop the new unique constraint and the column
            $table->dropUnique(['rater_id', 'rated_user_id', 'offer_id']);
            $table->dropForeign(['offer_id']);
            $table->dropColumn('offer_id');
        });
    }
};
