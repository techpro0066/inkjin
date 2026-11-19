<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_fee')) {
            return;
        }

        Schema::create('platform_fee', function (Blueprint $table) {
            $table->char('country', 2)->primary();
            $table->char('currency', 3)->index();
            $table->decimal('amount', 10, 2)->default(10.00);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_fee');
    }
};
