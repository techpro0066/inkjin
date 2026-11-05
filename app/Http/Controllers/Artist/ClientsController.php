<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Mail\GuestSpotOpenMail;
use App\Mail\WaitlistBooksOpenMail;
use App\Models\GuestSpot;
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
        $signupMode = $this->normalizeWaitlistSignupMode($userDetail?->waitlist_signup_mode);
        $guestAutoNotify = (bool) ($userDetail?->guest_waitlist_auto_notify ?? true);

        return view('artist.clients.index', [
            'clients' => $payload['clients'],
            'waitlist' => $payload['waitlist'],
            'guestWaitlist' => $payload['guest_waitlist'],
            'stats' => $payload['stats'],
            'currencySymbol' => '€',
            'booksOpen' => $booksOpen,
            'waitlistSignupMode' => $signupMode,
            'guestWaitlistAutoNotify' => $guestAutoNotify,
            'showWaitlistNotifyButton' => $waitlistPendingCount > 0 && $booksOpen,
            'waitlistPendingCount' => $waitlistPendingCount,
        ]);
    }

    public function updateWaitlistSignup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:name_email,request_form'],
        ]);

        $userDetail = $request->user()->userDetail;
        if (! $userDetail) {
            return response()->json(['message' => 'Artist profile not found.'], 404);
        }

        $mode = $this->normalizeWaitlistSignupMode($validated['mode']);
        $userDetail->forceFill(['waitlist_signup_mode' => $mode])->save();

        return response()->json([
            'success' => true,
            'mode' => $mode,
        ]);
    }

    public function updateGuestWaitlistAutoNotify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $userDetail = $request->user()->userDetail;
        if (! $userDetail) {
            return response()->json(['message' => 'Artist profile not found.'], 404);
        }

        $enabled = (bool) $validated['enabled'];
        $userDetail->forceFill(['guest_waitlist_auto_notify' => $enabled])->save();

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
        ]);
    }

    public function notifyGuestWaitlist(Request $request): JsonResponse
    {
        $artist = $request->user();
        $userDetail = $artist->userDetail;

        if (! $userDetail || $userDetail->user_name === null || $userDetail->user_name === '') {
            return response()->json(['message' => 'Artist profile not found.'], 404);
        }

        $validated = $request->validate([
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['integer', 'min:1'],
            'guest_spot_id' => ['sometimes', 'integer', 'min:1'],
        ]);

        $ids = collect($validated['ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $guestSpotId = isset($validated['guest_spot_id']) ? (int) $validated['guest_spot_id'] : 0;

        $query = Waitlist::query()
            ->guestOnly()
            ->with('guestSpot')
            ->where('user_id', $artist->id);

        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids->all());
        } elseif ($guestSpotId > 0) {
            $query->where('guest_spot_id', $guestSpotId)
                ->where('status', Waitlist::STATUS_PENDING);
        } else {
            return response()->json([
                'message' => 'No guest waitlist subscribers selected.',
            ], 422);
        }

        if ($guestSpotId > 0) {
            $query->where('guest_spot_id', $guestSpotId);
        }

        $entries = $query->orderBy('id')->get();

        if ($entries->isEmpty()) {
            return response()->json([
                'message' => 'No guest waitlist subscribers to notify.',
            ], 422);
        }

        $artistName = $userDetail->publicDisplayName()
            ?: trim($artist->first_name.' '.$artist->last_name);
        if ($artistName === '') {
            $artistName = 'Your artist';
        }
        $artistHandle = trim((string) $userDetail->user_name);
        $artistInitials = $userDetail->publicDisplayInitials();

        $sent = 0;
        $failed = 0;

        foreach ($entries as $entry) {
            $openSpot = $entry->guestSpot;
            if ($openSpot) {
                $openSpot->ensureCompletedStatus();
            }

            $isOpen = $openSpot !== null
                && $openSpot->effectiveStatusKey() === 'available'
                && $openSpot->isReservable();

            if (! $isOpen) {
                $failed++;
                Log::warning('Guest waitlist notify skipped — linked guest spot is not available', [
                    'waitlist_id' => $entry->id,
                    'artist_user_id' => $artist->id,
                    'guest_spot_id' => $entry->guest_spot_id,
                ]);
                continue;
            }

            $cityShort = trim((string) ($openSpot->city ?? ''));
            if ($cityShort === '') {
                $cityShort = 'your city';
            }
            $dateRangeLabel = $this->guestSpotDateRangeLabel($openSpot);
            $locationLabel = $this->guestSpotLocationLabel($openSpot);
            $subjectDates = $dateRangeLabel !== '' ? ', '.$dateRangeLabel : '';
            $subjectLine = $artistName.' is guesting in '.$cityShort.$subjectDates;
            $guestSpotUrl = route('public.guest-spot', [
                'user_name' => $userDetail->user_name,
                'guestSpotSlug' => $openSpot->publicSlug(),
            ]);

            try {
                Mail::to($entry->email)->send(new GuestSpotOpenMail(
                    recipientName: $entry->name,
                    artistName: $artistName,
                    artistHandle: $artistHandle,
                    artistInitials: $artistInitials,
                    city: $cityShort,
                    dateRangeLabel: $dateRangeLabel,
                    locationLabel: $locationLabel,
                    guestSpotUrl: $guestSpotUrl,
                    subjectLine: $subjectLine,
                    userDetail: $userDetail,
                ));
                $entry->update(['status' => Waitlist::STATUS_SENT]);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Failed to send guest waitlist open email', [
                    'waitlist_id' => $entry->id,
                    'artist_user_id' => $artist->id,
                    'email' => $entry->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($sent === 0) {
            return response()->json([
                'message' => $failed > 0
                    ? 'Could not send guest waitlist emails. That guest spot must be Available first.'
                    : 'Could not send guest waitlist emails. Please try again.',
                'sent' => 0,
                'failed' => $failed,
            ], 422);
        }

        $guestWaitlist = $this->clientService->buildGuestWaitlistForArtist((int) $artist->id);

        return response()->json([
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'message' => $failed > 0
                ? "Notified {$sent} subscriber(s). {$failed} email(s) could not be sent."
                : ($sent === 1 ? 'Email sent.' : "Notified {$sent} guest waitlist subscriber(s)."),
            'guest_waitlist' => $guestWaitlist,
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

        $validated = $request->validate([
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        $ids = collect($validated['ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $query = Waitlist::query()
            ->booksOnly()
            ->with('customRequest')
            ->where('user_id', $artist->id);
        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids->all());
        } else {
            $query->where('status', Waitlist::STATUS_PENDING);
        }

        $entries = $query->orderBy('id')->get();

        if ($entries->isEmpty()) {
            return response()->json([
                'message' => 'No waitlist subscribers to notify.',
            ], 422);
        }

        $profileUrl = route('public.artist', ['username' => $userDetail->user_name]);
        if ($userDetail && $artist) {
            $userDetail->setRelation('user', $artist);
        }
        $sent = 0;
        $failed = 0;

        foreach ($entries as $entry) {
            try {
                Mail::to($entry->email)->send(new WaitlistBooksOpenMail(
                    waitlist: $entry,
                    userDetail: $userDetail,
                    profileUrl: $profileUrl,
                ));
                $entry->update(['status' => Waitlist::STATUS_SENT]);

                if ($entry->custom_request_id) {
                    $entry->loadMissing('customRequest');
                    $entry->customRequest?->releaseFromWaitlist();
                }

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
            'pending_count' => $waitlistPendingCount,
        ]);
    }

    private function normalizeWaitlistSignupMode(?string $mode): string
    {
        return $mode === Waitlist::SIGNUP_MODE_REQUEST_FORM
            ? Waitlist::SIGNUP_MODE_REQUEST_FORM
            : Waitlist::SIGNUP_MODE_NAME_EMAIL;
    }

    private function guestSpotDateRangeLabel(GuestSpot $spot): string
    {
        if (! $spot->from_date || ! $spot->to_date) {
            return '';
        }

        $from = $spot->from_date;
        $to = $spot->to_date;
        if ($from->format('Y-m') === $to->format('Y-m')) {
            return $from->format('M j').' to '.$to->format('j');
        }

        if ($from->format('Y') === $to->format('Y')) {
            return $from->format('M j').' to '.$to->format('M j');
        }

        return $from->format('M j, Y').' to '.$to->format('M j, Y');
    }

    private function guestSpotLocationLabel(GuestSpot $spot): string
    {
        $lines = $spot->clientLocationLines();
        if ($lines !== []) {
            return implode(', ', $lines);
        }

        return trim((string) ($spot->listLocationLabel() ?? ''));
    }
}
