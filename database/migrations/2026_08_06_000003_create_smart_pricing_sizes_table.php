<?php

use App\Database\SafelyDropsTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafelyDropsTables;

    public function up(): void
    {
        if (Schema::hasTable('smart_pricing_sizes')) {
            return;
        }

        Schema::create('smart_pricing_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('kind', ['between', 'less_than', 'more_than'])->default('between');
            $table->decimal('size_min', 8, 2)->nullable();
            $table->decimal('size_max', 8, 2)->nullable();
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2);
            $table->string('sessions', 50);
            $table->decimal('duration', 8, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('smart_pricing_sizes');
    }
};
