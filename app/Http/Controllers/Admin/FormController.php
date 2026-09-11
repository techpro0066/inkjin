<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsentFormQuestion;
use App\Models\Question;
use Illuminate\Support\Facades\Auth;

class FormController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $defaultQuestions = Question::query()
            ->where('user_id', $userId)
            ->where('form_context', 'default')
            ->with('sorting')
            ->get();

        $customQuestions = Question::query()
            ->where('user_id', $userId)
            ->where('form_context', 'custom')
            ->with('sorting')
            ->get();

        $defaultQuestions = $defaultQuestions
            ->sortBy(function (Question $question) {
                return optional($question->sorting)->order ?? $question->id;
            })
            ->values()
            ->map(function (Question $question) {
                $question->setAttribute('order', optional($question->sorting)->order ?? $question->id);
                $question->setAttribute('is_active', optional($question->sorting)->is_active ?? true);

                return $question;
            });

        $customQuestions = $customQuestions
            ->sortBy(function (Question $question) {
                return optional($question->sorting)->order ?? $question->id;
            })
            ->values()
            ->map(function (Question $question) {
                $question->setAttribute('order', optional($question->sorting)->order ?? $question->id);
                $question->setAttribute('is_active', optional($question->sorting)->is_active ?? true);

                return $question;
            });

        $consentQuestions = ConsentFormQuestion::query()
            ->where('user_id', ConsentFormQuestion::SYSTEM_USER_ID)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return view('admin.forms.index', compact('defaultQuestions', 'customQuestions', 'consentQuestions'));
    }
}
