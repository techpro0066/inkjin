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
        if (Schema::hasTable('studios')) {
            return;
        }

        Schema::create('studios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('stripe_account_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('studios');
    }
};
