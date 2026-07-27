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
        if (! Schema::hasTable('artist_faqs')) {
            Schema::create('artist_faqs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('question');
                $table->text('answer');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'sort_order']);
            });
        }

        if (Schema::hasTable('user_details') && ! Schema::hasColumn('user_details', 'display_faq')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->boolean('display_faq')->default(false)->after('display_guest_spots');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_details') && Schema::hasColumn('user_details', 'display_faq')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->dropColumn('display_faq');
            });
        }

        $this->dropTablesSafely('artist_faqs');
    }
};
