<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_spots', function (Blueprint $table) {
            $table->unsignedSmallInteger('response_deadline')->nullable()->after('sort_order');
            $table->string('response_deadline_unit', 16)->nullable()->after('response_deadline');
            $table->unsignedSmallInteger('buffer_days_before')->default(0)->after('response_deadline_unit');
            $table->unsignedSmallInteger('buffer_days_after')->default(0)->after('buffer_days_before');
            $table->unsignedSmallInteger('number_of_spots')->default(0)->after('buffer_days_after');
            $table->string('studio_name')->nullable()->after('number_of_spots');
            $table->text('studio_address')->nullable()->after('studio_name');
            $table->string('street_number', 50)->nullable()->after('studio_address');
            $table->string('street_name')->nullable()->after('street_number');
            $table->string('studio_city')->nullable()->after('street_name');
            $table->string('studio_state')->nullable()->after('studio_city');
            $table->string('postal_code', 50)->nullable()->after('studio_state');
            $table->string('studio_country')->nullable()->after('postal_code');
            $table->string('google_maps_link', 500)->nullable()->after('studio_country');
            $table->decimal('latitude', 10, 7)->nullable()->after('google_maps_link');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('guest_spots', function (Blueprint $table) {
            $table->dropColumn([
                'response_deadline',
                'response_deadline_unit',
                'buffer_days_before',
                'buffer_days_after',
                'number_of_spots',
                'studio_name',
                'studio_address',
                'street_number',
                'street_name',
                'studio_city',
                'studio_state',
                'postal_code',
                'studio_country',
                'google_maps_link',
                'latitude',
                'longitude',
            ]);
        });
    }
};
