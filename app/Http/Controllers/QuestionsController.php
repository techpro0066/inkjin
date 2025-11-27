<?php

namespace App\Http\Controllers;

use App\Models\UserQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class QuestionsController extends Controller
{
    /**
     * Display a listing of the user's questions.
     */
    public function index()
    {
        $questions = UserQuestion::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        return view('questions.index', compact('questions'));
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'question' => 'required|string|min:10|max:500',
                'type' => 'required|in:free,select,radio,image',
                'options' => 'nullable|array|required_if:type,select,radio',
                'options.*' => 'required|string|max:255',
                'max_images' => 'nullable|integer|min:1|max:20|required_if:type,image',
                'status' => 'required|in:active,inactive',
            ]);

            // If type is select or radio, ensure options array is not empty
            if (in_array($validated['type'], ['select', 'radio']) && (empty($validated['options']) || count($validated['options']) < 2)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['options' => ['At least 2 options are required for select and radio types.']],
                ], 422);
            }

            // If type is free or image, set options to null
            if (in_array($validated['type'], ['free', 'image'])) {
                $validated['options'] = null;
            }
            
            // If type is not image, set max_images to null
            if ($validated['type'] !== 'image') {
                $validated['max_images'] = null;
            }

            $validated['user_id'] = Auth::id();
            $question = UserQuestion::create($validated);

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
            $question = UserQuestion::where('user_id', Auth::id())->findOrFail($id);

            $validated = $request->validate([
                'question' => 'required|string|min:10|max:500',
                'type' => 'required|in:free,select,radio,image',
                'options' => 'nullable|array|required_if:type,select,radio',
                'options.*' => 'required|string|max:255',
                'max_images' => 'nullable|integer|min:1|max:20|required_if:type,image',
                'status' => 'required|in:active,inactive',
            ]);

            // If type is select or radio, ensure options array is not empty
            if (in_array($validated['type'], ['select', 'radio']) && (empty($validated['options']) || count($validated['options']) < 2)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['options' => ['At least 2 options are required for select and radio types.']],
                ], 422);
            }

            // If type is free or image, set options to null
            if (in_array($validated['type'], ['free', 'image'])) {
                $validated['options'] = null;
            }
            
            // If type is not image, set max_images to null
            if ($validated['type'] !== 'image') {
                $validated['max_images'] = null;
            }

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found',
            ], 404);
        }
    }

    /**
     * Remove the specified question.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $question = UserQuestion::where('user_id', Auth::id())->findOrFail($id);
            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found',
            ], 404);
        }
    }
}

