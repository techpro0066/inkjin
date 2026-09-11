<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_form_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('consent_form_questions', 'order')) {
                $table->unsignedInteger('order')->default(0)->after('enabled');
                $table->index(['user_id', 'order']);
            }
        });

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'consent_questions_seeded_at')) {
                $table->timestamp('consent_questions_seeded_at')->nullable()->after('aftercare_content');
            }
        });

        $this->assignSystemQuestionOrder();
        $this->migrateSortingToOwnedQuestions();
        $this->markExistingOwnersAsSeeded();

        Schema::dropIfExists('consent_form_question_sorting');
    }

    public function down(): void
    {
        if (! Schema::hasTable('consent_form_question_sorting')) {
            Schema::create('consent_form_question_sorting', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('consent_form_question_id')->constrained('consent_form_questions')->cascadeOnDelete();
                $table->unsignedInteger('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'consent_form_question_id'], 'consent_q_sorting_user_question_unique');
                $table->index(['user_id', 'order']);
            });
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'consent_questions_seeded_at')) {
                $table->dropColumn('consent_questions_seeded_at');
            }
        });

        Schema::table('consent_form_questions', function (Blueprint $table) {
            if (Schema::hasColumn('consent_form_questions', 'order')) {
                $table->dropIndex(['user_id', 'order']);
                $table->dropColumn('order');
            }
        });
    }

    private function assignSystemQuestionOrder(): void
    {
        $systemUserId = 1;
        $questions = DB::table('consent_form_questions')
            ->where('user_id', $systemUserId)
            ->orderByRaw("CASE question_type WHEN 'health' THEN 1 WHEN 'risk' THEN 2 WHEN 'aftercare' THEN 3 ELSE 4 END")
            ->orderBy('id')
            ->get(['id']);

        $order = 0;
        foreach ($questions as $question) {
            $order++;
            DB::table('consent_form_questions')
                ->where('id', $question->id)
                ->update(['order' => $order]);
        }
    }

    private function migrateSortingToOwnedQuestions(): void
    {
        if (! Schema::hasTable('consent_form_question_sorting')) {
            return;
        }

        $systemUserId = 1;
        $artistIds = DB::table('consent_form_question_sorting')
            ->where('user_id', '!=', $systemUserId)
            ->distinct()
            ->pluck('user_id');

        $now = now();

        foreach ($artistIds as $artistId) {
            $artistId = (int) $artistId;
            $sortings = DB::table('consent_form_question_sorting as s')
                ->join('consent_form_questions as q', 'q.id', '=', 's.consent_form_question_id')
                ->where('s.user_id', $artistId)
                ->orderBy('s.order')
                ->orderBy('s.id')
                ->get([
                    's.id as sorting_id',
                    's.order as sorting_order',
                    's.is_active as sorting_active',
                    'q.id as question_id',
                    'q.user_id as question_user_id',
                    'q.question_type',
                    'q.translations',
                    'q.enabled as question_enabled',
                ]);

            $order = 0;
            foreach ($sortings as $row) {
                $order++;
                $questionUserId = (int) $row->question_user_id;

                if ($questionUserId === $artistId) {
                    DB::table('consent_form_questions')
                        ->where('id', $row->question_id)
                        ->update([
                            'order' => $order,
                            'enabled' => (bool) $row->sorting_active,
                            'updated_at' => $now,
                        ]);
                    continue;
                }

                if ($questionUserId !== $systemUserId) {
                    continue;
                }

                DB::table('consent_form_questions')->insert([
                    'user_id' => $artistId,
                    'question_type' => $row->question_type,
                    'translations' => $row->translations,
                    'enabled' => (bool) $row->sorting_active,
                    'order' => $order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->ensureUserDetail($artistId);
            DB::table('user_details')
                ->where('user_id', $artistId)
                ->update(['consent_questions_seeded_at' => $now]);
        }
    }

    private function markExistingOwnersAsSeeded(): void
    {
        $systemUserId = 1;
        $now = now();

        $ownerIds = DB::table('consent_form_questions')
            ->where('user_id', '!=', $systemUserId)
            ->distinct()
            ->pluck('user_id');

        foreach ($ownerIds as $ownerId) {
            $ownerId = (int) $ownerId;
            $this->ensureUserDetail($ownerId);

            $detail = DB::table('user_details')->where('user_id', $ownerId)->first();
            if ($detail && empty($detail->consent_questions_seeded_at)) {
                DB::table('user_details')
                    ->where('user_id', $ownerId)
                    ->update(['consent_questions_seeded_at' => $now]);
            }

            // Ensure owned questions have sequential order when still 0.
            $owned = DB::table('consent_form_questions')
                ->where('user_id', $ownerId)
                ->orderBy('order')
                ->orderBy('id')
                ->get(['id', 'order']);

            $needsReorder = $owned->every(fn ($q) => (int) $q->order === 0);
            if (! $needsReorder) {
                continue;
            }

            $order = 0;
            foreach ($owned as $question) {
                $order++;
                DB::table('consent_form_questions')
                    ->where('id', $question->id)
                    ->update(['order' => $order]);
            }
        }
    }

    private function ensureUserDetail(int $userId): void
    {
        $exists = DB::table('user_details')->where('user_id', $userId)->exists();
        if ($exists) {
            return;
        }

        $now = now();
        DB::table('user_details')->insert([
            'user_id' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
