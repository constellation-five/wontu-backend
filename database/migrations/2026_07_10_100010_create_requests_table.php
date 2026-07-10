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
        Schema::create('requests', function (Blueprint $table) {
            $table->id('request_id'); 
            $table->uuid('requester_id'); 
            $table->string('location_label', 255)->nullable();
            $table->geometry('location', subtype: 'point', srid: 4326)->nullable();
            $table->string('item_name', 64);
            $table->enum('category', ['food', 'other']);
            $table->dateTime('arrival_time');
            $table->integer('total_votes')->default(0);
            $table->timestamps(); 

            $table->foreign('requester_id')->references('user_id')->on('users')->onDelete('cascade');
        });

        DB::statement(
            "UPDATE requests SET location = ST_GeomFromText('POINT(106.8456 -6.2088)', 4326, 'axis-order=long-lat') WHERE location IS NULL",
        );

        Schema::table('requests', function (Blueprint $table) {
            $table->geometry('location', subtype: 'point', srid: 4326)->nullable(false)->change();
            $table->spatialIndex('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};