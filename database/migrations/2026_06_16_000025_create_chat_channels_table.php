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
        if (Schema::hasTable('chat_channels')) {
            return;
        }

        Schema::create('chat_channels', function (Blueprint $table) {
            $table->id();
            $table->string('stream_channel_id', 100)->unique();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('artist_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['client_user_id', 'artist_user_id']);
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('chat_channels');
    }
};
