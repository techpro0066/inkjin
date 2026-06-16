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
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('must_set_password')->default(false);
            $table->enum('role', ['admin', 'artist', 'user'])->default('user');
            $table->enum('on_boarding', ['yes', 'no'])->default('no');
            $table->tinyInteger('on_app')->default(0);
            $table->unsignedBigInteger('app_id')->nullable();
            $table->string('country_user_belongs_in')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('users');
    }
};
