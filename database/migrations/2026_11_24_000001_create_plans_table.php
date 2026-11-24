<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('status', 16)->default('Draft');
            $table->string('audience', 16)->default('Artist');
            $table->string('visibility', 16)->default('Public');
            $table->string('badge')->nullable();
            $table->json('prices');
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('client_booking_fee')->default(false);
            $table->unsignedSmallInteger('included_artist_count')->nullable();
            $table->decimal('extra_artist_monthly_price', 10, 2)->nullable();
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->string('trial_fallback_plan')->nullable();
            $table->date('signup_cutoff_date')->nullable();
            $table->string('cutoff_replacement_plan')->nullable();
            $table->string('layout', 16)->default('card');
            $table->text('tagline')->nullable();
            $table->text('price_note')->nullable();
            $table->string('list_title')->nullable();
            $table->json('included_items')->nullable();
            $table->string('button_label')->nullable();
            $table->text('button_note')->nullable();
            $table->text('terms_intro')->nullable();
            $table->json('terms')->nullable();
            $table->text('terms_note')->nullable();
            $table->string('trial_button_label')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
