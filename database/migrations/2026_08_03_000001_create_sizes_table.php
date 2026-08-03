<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->decimal('cm_min', 8, 2)->nullable();
            $table->decimal('cm_max', 8, 2)->nullable();
            $table->decimal('in_min', 8, 2)->nullable();
            $table->decimal('in_max', 8, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
