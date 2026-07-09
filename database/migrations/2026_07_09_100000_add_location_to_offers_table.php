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
        Schema::table('offers', function (Blueprint $table) {
            $table->string('location_label', 255)->nullable()->after('merchant_name');
            $table->geometry('location', subtype: 'point', srid: 4326)->nullable()->after('location_label');
        });

        // Backfill existing rows (SPATIAL indexes require NOT NULL) with a Jakarta
        // fallback point before the column is locked down and indexed below.
        // MySQL 8's default axis order for SRID 4326 is lat/long, which is
        // surprising next to the app's lng/lat convention (LatLngLiteral, GeoJSON) —
        // 'axis-order=long-lat' pins WKT text to POINT(lng lat) everywhere instead.
        DB::statement(
            "UPDATE offers SET location = ST_GeomFromText('POINT(106.8456 -6.2088)', 4326, 'axis-order=long-lat') WHERE location IS NULL",
        );

        Schema::table('offers', function (Blueprint $table) {
            $table->geometry('location', subtype: 'point', srid: 4326)->nullable(false)->change();
            $table->spatialIndex('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropSpatialIndex(['location']);
            $table->dropColumn(['location_label', 'location']);
        });
    }
};
