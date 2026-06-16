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
        if (Schema::hasTable('questions')) {
            return;
        }

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('question');
            $table->text('description')->nullable();
            $table->text('placeholder')->nullable();
            $table->enum('type', ['input', 'textarea', 'toggle', 'select', 'image', 'radio']);
            $table->json('options')->nullable();
            $table->unsignedTinyInteger('max_images')->nullable();
            $table->boolean('is_required');
            $table->string('form_context');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('questions');
    }
};
