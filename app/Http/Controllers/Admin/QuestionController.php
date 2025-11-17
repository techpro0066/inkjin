<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    /**
     * Display a listing of the questions.
     */
    public function index()
    {
        $questions = Question::orderBy('created_at', 'desc')->get();
        return view('admin.questions.index', compact('questions'));
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'question' => 'required|string|min:10|max:500',
                'status' => 'required|in:active,inactive',
            ]);

            $question = Question::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Question created successfully',
                'question' => $question,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $question = Question::findOrFail($id);

            $validated = $request->validate([
                'question' => 'required|string|min:10|max:500',
                'status' => 'required|in:active,inactive',
            ]);

            $question->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'question' => $question,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove the specified question.
     */
    public function destroy($id): JsonResponse
    {
        $question = Question::findOrFail($id);
        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully',
        ]);
    }
}

