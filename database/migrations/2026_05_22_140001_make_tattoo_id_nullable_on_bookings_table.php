<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['tattoo_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('tattoo_id')->nullable()->change();
            $table->foreign('tattoo_id')->references('id')->on('inkjin_tattoos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['tattoo_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('tattoo_id')->nullable(false)->change();
            $table->foreign('tattoo_id')->references('id')->on('inkjin_tattoos')->restrictOnDelete();
        });
    }
};
