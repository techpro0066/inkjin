<?php

use App\Database\SafelyDropsTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafelyDropsTables;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_not_registered', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('country');
            $table->string('hear_about_us');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTablesSafely('user_not_registered');
    }
};
