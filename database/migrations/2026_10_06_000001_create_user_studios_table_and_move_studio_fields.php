<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns moved off user_details onto user_studios.
     * Kept on user_details: country, city (regional/profile).
     */
    private array $columnsToDrop = [
        'studio_name',
        'studio_address',
        'street_name',
        'street_number',
        'state',
        'postal_code',
        'google_maps_link',
        'workspace_type',
        'studio_revenue_artist_percent',
        'studio_relationship_type',
        'studio_id',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('user_studios')) {
            Schema::create('user_studios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('studio_id')->nullable()->constrained('studios')->nullOnDelete();

                // 0 inactive, 1 active, 2 join_pending, 3 split_proposed
                $table->unsignedTinyInteger('status')->default(0);
                $table->enum('connection_type', [
                    'not_on_bookpay',
                    'on_bookpay',
                    'not_connected',
                ])->default('not_on_bookpay');
                $table->enum('relationship', [
                    'co_owner',
                    'resident',
                    'collective_member',
                    'apprentice',
                    'other',
                ])->nullable();
                $table->string('workspace_type', 32)->nullable();

                // Address snapshot (for not-on-bookpay / own space)
                $table->string('studio_name')->nullable();
                $table->text('studio_address')->nullable();
                $table->string('street_name')->nullable();
                $table->string('street_number', 50)->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('country')->nullable();
                $table->string('google_maps_link')->nullable();

                $table->boolean('payout')->default(false); // yes/no
                $table->unsignedTinyInteger('revenue_split')->nullable(); // artist %
                $table->unsignedTinyInteger('proposed_revenue_split')->nullable(); // studio counter-offer

                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['user_id', 'studio_id']);
            });
        } elseif (! Schema::hasColumn('user_studios', 'proposed_revenue_split')) {
            Schema::table('user_studios', function (Blueprint $table) {
                $table->unsignedTinyInteger('proposed_revenue_split')->nullable()->after('revenue_split');
            });
        }

        $this->backfillFromUserDetails();
        $this->dropUserDetailStudioColumns();
    }

    public function down(): void
    {
        $this->restoreUserDetailStudioColumns();
        $this->copyBackToUserDetails();

        Schema::dropIfExists('user_studios');
    }

    private function backfillFromUserDetails(): void
    {
        if (! Schema::hasTable('user_details') || ! Schema::hasTable('user_studios')) {
            return;
        }

        $hasStudioId = Schema::hasColumn('user_details', 'studio_id');
        $hasStudioName = Schema::hasColumn('user_details', 'studio_name');
        $hasStudioAddress = Schema::hasColumn('user_details', 'studio_address');
        $hasStreetName = Schema::hasColumn('user_details', 'street_name');
        $hasStreetNumber = Schema::hasColumn('user_details', 'street_number');
        $hasState = Schema::hasColumn('user_details', 'state');
        $hasPostal = Schema::hasColumn('user_details', 'postal_code');
        $hasMaps = Schema::hasColumn('user_details', 'google_maps_link');
        $hasWorkspace = Schema::hasColumn('user_details', 'workspace_type');
        $hasRelationship = Schema::hasColumn('user_details', 'studio_relationship_type');
        $hasSplit = Schema::hasColumn('user_details', 'studio_revenue_artist_percent');
        $hasPaymentType = Schema::hasColumn('user_details', 'payment_type');
        $hasCity = Schema::hasColumn('user_details', 'city');
        $hasCountry = Schema::hasColumn('user_details', 'country');

        $select = ['id', 'user_id'];
        foreach ([
            'studio_id' => $hasStudioId,
            'studio_name' => $hasStudioName,
            'studio_address' => $hasStudioAddress,
            'street_name' => $hasStreetName,
            'street_number' => $hasStreetNumber,
            'state' => $hasState,
            'postal_code' => $hasPostal,
            'google_maps_link' => $hasMaps,
            'workspace_type' => $hasWorkspace,
            'studio_relationship_type' => $hasRelationship,
            'studio_revenue_artist_percent' => $hasSplit,
            'payment_type' => $hasPaymentType,
            'city' => $hasCity,
            'country' => $hasCountry,
        ] as $col => $exists) {
            if ($exists) {
                $select[] = $col;
            }
        }

        DB::table('user_details')
            ->select($select)
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (
                $hasStudioId,
                $hasStudioName,
                $hasStudioAddress,
                $hasStreetName,
                $hasStreetNumber,
                $hasState,
                $hasPostal,
                $hasMaps,
                $hasWorkspace,
                $hasRelationship,
                $hasSplit,
                $hasPaymentType,
                $hasCity,
                $hasCountry
            ) {
                $now = now();
                $inserts = [];

                foreach ($rows as $row) {
                    $studioId = $hasStudioId ? $row->studio_id : null;
                    $studioName = $hasStudioName ? $this->nullableString($row->studio_name) : null;
                    $studioAddress = $hasStudioAddress ? $this->nullableString($row->studio_address) : null;
                    $streetName = $hasStreetName ? $this->nullableString($row->street_name) : null;
                    $streetNumber = $hasStreetNumber ? $this->nullableString($row->street_number) : null;
                    $state = $hasState ? $this->nullableString($row->state) : null;
                    $postal = $hasPostal ? $this->nullableString($row->postal_code) : null;
                    $maps = $hasMaps ? $this->nullableString($row->google_maps_link) : null;
                    $workspace = $hasWorkspace ? $this->nullableString($row->workspace_type) : null;
                    $relationship = $hasRelationship ? $this->nullableString($row->studio_relationship_type) : null;
                    $split = $hasSplit ? $row->studio_revenue_artist_percent : null;
                    $city = $hasCity ? $this->nullableString($row->city) : null;
                    $country = $hasCountry ? $this->nullableString($row->country) : null;

                    // Skip rows with no studio-linked details.
                    $hasDetails = $studioId
                        || $studioName
                        || $studioAddress
                        || $streetName
                        || $streetNumber
                        || $state
                        || $postal
                        || $maps
                        || $workspace
                        || $relationship
                        || $split !== null;

                    if (! $hasDetails) {
                        continue;
                    }

                    $already = DB::table('user_studios')
                        ->where('user_id', $row->user_id)
                        ->when(
                            $studioId,
                            fn ($q) => $q->where('studio_id', $studioId),
                            fn ($q) => $q->whereNull('studio_id')
                        )
                        ->exists();

                    if ($already) {
                        continue;
                    }

                    $paymentType = $hasPaymentType ? (string) ($row->payment_type ?? '') : '';

                    $inserts[] = [
                        'user_id' => $row->user_id,
                        'studio_id' => $studioId,
                        'status' => 1,
                        'connection_type' => 'not_on_bookpay',
                        'relationship' => $this->validRelationship($relationship),
                        'workspace_type' => $workspace,
                        'studio_name' => $studioName,
                        'studio_address' => $studioAddress,
                        'street_name' => $streetName,
                        'street_number' => $streetNumber,
                        'city' => $city,
                        'state' => $state,
                        'postal_code' => $postal,
                        'country' => $country,
                        'google_maps_link' => $maps,
                        'payout' => $paymentType === 'studio_account',
                        'revenue_split' => is_numeric($split) ? (int) $split : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($inserts !== []) {
                    DB::table('user_studios')->insert($inserts);
                }
            });
    }

    private function dropUserDetailStudioColumns(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        // Drop FK before studio_id column.
        if (Schema::hasColumn('user_details', 'studio_id')) {
            Schema::table('user_details', function (Blueprint $table) {
                try {
                    $table->dropForeign(['studio_id']);
                } catch (\Throwable) {
                    // Some DBs name FKs differently; continue to drop column.
                }
            });
        }

        $toDrop = array_values(array_filter(
            $this->columnsToDrop,
            fn (string $col) => Schema::hasColumn('user_details', $col)
        ));

        if ($toDrop === []) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) use ($toDrop) {
            $table->dropColumn($toDrop);
        });
    }

    private function restoreUserDetailStudioColumns(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'studio_name')) {
                $table->string('studio_name')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'studio_address')) {
                $table->text('studio_address')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'street_name')) {
                $table->string('street_name')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'street_number')) {
                $table->string('street_number')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'state')) {
                $table->string('state')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'postal_code')) {
                $table->string('postal_code')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'google_maps_link')) {
                $table->string('google_maps_link')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'workspace_type')) {
                $table->string('workspace_type', 32)->nullable();
            }
            if (! Schema::hasColumn('user_details', 'studio_id')) {
                $table->foreignId('studio_id')->nullable()->constrained('studios')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('user_details', 'studio_revenue_artist_percent')) {
                $table->unsignedTinyInteger('studio_revenue_artist_percent')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'studio_relationship_type')) {
                $table->enum('studio_relationship_type', [
                    'co_owner',
                    'resident',
                    'collective_member',
                    'apprentice',
                    'other',
                ])->nullable();
            }
        });
    }

    private function copyBackToUserDetails(): void
    {
        if (! Schema::hasTable('user_details') || ! Schema::hasTable('user_studios')) {
            return;
        }

        $links = DB::table('user_studios')
            ->orderByDesc('status')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id');

        foreach ($links as $link) {
            DB::table('user_details')
                ->where('user_id', $link->user_id)
                ->update([
                    'studio_id' => $link->studio_id,
                    'studio_name' => $link->studio_name,
                    'studio_address' => $link->studio_address,
                    'street_name' => $link->street_name,
                    'street_number' => $link->street_number,
                    'state' => $link->state,
                    'postal_code' => $link->postal_code,
                    'google_maps_link' => $link->google_maps_link,
                    'workspace_type' => $link->workspace_type,
                    'studio_relationship_type' => $link->relationship,
                    'studio_revenue_artist_percent' => $link->revenue_split,
                    'updated_at' => now(),
                ]);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function validRelationship(?string $value): ?string
    {
        $allowed = ['co_owner', 'resident', 'collective_member', 'apprentice', 'other'];

        return $value && in_array($value, $allowed, true) ? $value : null;
    }
};
