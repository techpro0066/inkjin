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
        if (Schema::hasTable('availabilities')) {
            return;
        }

        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('day_of_week', 20);
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['user_id', 'day_of_week', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('availabilities');
    }
};
