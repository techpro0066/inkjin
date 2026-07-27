<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Mail\WaitlistBooksOpenMail;
use App\Models\Waitlist;
use App\Services\ArtistClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ClientsController extends Controller
{
    public function __construct(private ArtistClientService $clientService) {}

    public function index(Request $request): View
    {
        $payload = $this->clientService->buildForArtist((int) $request->user()->id);
        $userDetail = $request->user()->userDetail;
        $booksOpen = ($userDetail?->availability_status ?? '') !== 'closed';
        $waitlistPendingCount = collect($payload['waitlist'])
            ->where('status_key', Waitlist::STATUS_PENDING)
            ->count();

        return view('artist.clients.index', [
            'clients' => $payload['clients'],
            'waitlist' => $payload['waitlist'],
            'stats' => $payload['stats'],
            'currencySymbol' => '€',
            'showWaitlistNotifyButton' => $waitlistPendingCount > 0 && $booksOpen,
        ]);
    }

    public function notifyWaitlist(Request $request): JsonResponse
    {
        $artist = $request->user();
        $userDetail = $artist->userDetail;

        if (! $userDetail || $userDetail->user_name === null || $userDetail->user_name === '') {
            return response()->json(['message' => 'Artist profile not found.'], 404);
        }

        if (($userDetail->availability_status ?? '') === 'closed') {
            return response()->json([
                'message' => 'Your books are closed. Open your books before notifying the waitlist.',
            ], 422);
        }

        $entries = Waitlist::query()
            ->where('user_id', $artist->id)
            ->where('status', Waitlist::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        if ($entries->isEmpty()) {
            return response()->json([
                'message' => 'No pending waitlist subscribers to notify.',
            ], 422);
        }

        $artistName = $artist->userDetail?->publicDisplayName()
            ?: trim($artist->first_name.' '.$artist->last_name);
        if ($artistName === '') {
            $artistName = 'Your artist';
        }

        $profileUrl = route('public.artist', ['username' => $userDetail->user_name]);
        $sent = 0;
        $failed = 0;

        foreach ($entries as $entry) {
            try {
                Mail::to($entry->email)->send(new WaitlistBooksOpenMail(
                    recipientName: $entry->name,
                    artistName: $artistName,
                    profileUrl: $profileUrl,
                ));
                $entry->update(['status' => Waitlist::STATUS_SENT]);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Failed to send waitlist books-open email', [
                    'waitlist_id' => $entry->id,
                    'artist_user_id' => $artist->id,
                    'email' => $entry->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($sent === 0) {
            return response()->json([
                'message' => 'Could not send waitlist emails. Please try again.',
                'sent' => 0,
                'failed' => $failed,
            ], 500);
        }

        $waitlist = $this->clientService->buildWaitlistForArtist((int) $artist->id);
        $waitlistPendingCount = collect($waitlist)->where('status_key', Waitlist::STATUS_PENDING)->count();
        $booksOpen = ($userDetail->availability_status ?? '') !== 'closed';

        return response()->json([
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'message' => $failed > 0
                ? "Notified {$sent} subscriber(s). {$failed} email(s) could not be sent."
                : "Notified {$sent} waitlist subscriber(s).",
            'waitlist' => $waitlist,
            'show_waitlist_notify_button' => $waitlistPendingCount > 0 && $booksOpen,
        ]);
    }
}
