<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\UserStudio;
use App\Services\RevenueSplitChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RevenueSplitController extends Controller
{
    public function propose(Request $request, RevenueSplitChangeService $splits): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! UserStudio::tableReady()) {
            return $this->failed($request, 'Studio link not found.', 404);
        }

        $validated = $request->validate([
            'user_studio_id' => ['required', 'integer'],
            'artist_percent' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $link = UserStudio::query()
            ->where('id', (int) $validated['user_studio_id'])
            ->where('user_id', (int) $user->id)
            ->first();

        if (! $link) {
            return $this->failed($request, 'Studio link not found.', 404);
        }

        try {
            $splits->proposeByArtist($user, $link, (int) $validated['artist_percent']);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'Could not send split request.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $message = 'Split request sent. The current split stays until the studio accepts.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'reload' => true,
            ]);
        }

        return redirect()->route('settings.payment')->with('success', $message);
    }

    private function failed(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return redirect()->route('settings.payment')->with('error', $message);
    }
}
