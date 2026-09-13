<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_answers', function (Blueprint $table) {
            if (! Schema::hasColumn('consent_answers', 'guardian_signed_at')) {
                $table->timestamp('guardian_signed_at')->nullable()->after('guardian_signature');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consent_answers', function (Blueprint $table) {
            if (Schema::hasColumn('consent_answers', 'guardian_signed_at')) {
                $table->dropColumn('guardian_signed_at');
            }
        });
    }
};
