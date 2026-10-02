<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            // Owner account (users.role = studio)
            if (! Schema::hasColumn('studios', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->unique('user_id');
            }

            // Media / branding (name + email already exist)
            if (! Schema::hasColumn('studios', 'image_url')) {
                $table->string('image_url')->nullable()->after('email');
            }
            if (! Schema::hasColumn('studios', 'logo_url')) {
                $table->string('logo_url')->nullable()->after('image_url');
            }

            // Studio details (Account → Studio details)
            if (! Schema::hasColumn('studios', 'studio_type')) {
                $table->string('studio_type', 32)->nullable()->after('logo_url'); // private|tattoo|home|collective
            }
            if (! Schema::hasColumn('studios', 'address')) {
                $table->text('address')->nullable()->after('studio_type');
            }
            if (! Schema::hasColumn('studios', 'street_name')) {
                $table->string('street_name')->nullable()->after('address');
            }
            if (! Schema::hasColumn('studios', 'street_number')) {
                $table->string('street_number', 50)->nullable()->after('street_name');
            }
            if (! Schema::hasColumn('studios', 'city')) {
                $table->string('city')->nullable()->after('street_number');
            }
            if (! Schema::hasColumn('studios', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (! Schema::hasColumn('studios', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('state');
            }
            if (! Schema::hasColumn('studios', 'country')) {
                $table->string('country')->nullable()->after('postal_code');
            }
            if (! Schema::hasColumn('studios', 'google_maps_link')) {
                $table->string('google_maps_link', 500)->nullable()->after('country');
            }
            if (! Schema::hasColumn('studios', 'business_phone')) {
                $table->string('business_phone')->nullable()->after('google_maps_link');
            }
            if (! Schema::hasColumn('studios', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('business_phone');
            }
            if (! Schema::hasColumn('studios', 'tattooing_since')) {
                $table->unsignedSmallInteger('tattooing_since')->nullable()->after('contact_email');
            }

            // Regional (Account → Regional)
            if (! Schema::hasColumn('studios', 'timezone')) {
                $table->string('timezone')->nullable()->after('tattooing_since');
            }
            if (! Schema::hasColumn('studios', 'date_time_format')) {
                $table->string('date_time_format')->nullable()->after('timezone');
            }
            if (! Schema::hasColumn('studios', 'size_unit')) {
                $table->enum('size_unit', ['cm', 'in'])->nullable()->after('date_time_format');
            }
        });

        // Backfill owner from matching studio-role users by email
        if (Schema::hasColumn('studios', 'user_id') && Schema::hasTable('users')) {
            $studios = DB::table('studios')->whereNull('user_id')->whereNotNull('email')->get(['id', 'email']);
            foreach ($studios as $studio) {
                $email = strtolower(trim((string) $studio->email));
                if ($email === '') {
                    continue;
                }

                $userId = DB::table('users')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->where('role', 'studio')
                    ->value('id');

                if ($userId) {
                    DB::table('studios')->where('id', $studio->id)->update(['user_id' => $userId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            if (Schema::hasColumn('studios', 'user_id')) {
                $table->dropUnique(['user_id']);
                $table->dropConstrainedForeignId('user_id');
            }

            $columns = [
                'image_url',
                'logo_url',
                'studio_type',
                'address',
                'street_name',
                'street_number',
                'city',
                'state',
                'postal_code',
                'country',
                'google_maps_link',
                'business_phone',
                'contact_email',
                'tattooing_since',
                'timezone',
                'date_time_format',
                'size_unit',
            ];

            $existing = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn('studios', $column)
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
