<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\UserStudio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ArtistJoinRequestController extends Controller
{
    public function show(Request $request, UserStudio $userStudio): View
    {
        if ($userStudio->status !== UserStudio::STATUS_JOIN_PENDING) {
            return view('studio.join-request-result', [
                'status' => 'already_handled',
                'artistName' => $this->artistName($userStudio),
                'studioName' => $this->studioName($userStudio),
            ]);
        }

        $userStudio->loadMissing(['user', 'studio']);

        return view('studio.join-request-accept', [
            'userStudio' => $userStudio,
            'artistName' => $this->artistName($userStudio),
            'studioName' => $this->studioName($userStudio),
            'relationshipLabel' => $this->relationshipLabel($userStudio),
            'artistPercent' => $userStudio->payout && $userStudio->revenue_split !== null
                ? (int) $userStudio->revenue_split
                : null,
            'acceptUrl' => URL::temporarySignedRoute(
                'studio.join-request.accept',
                now()->addDays(30),
                ['userStudio' => $userStudio->id]
            ),
            'declineUrl' => URL::temporarySignedRoute(
                'studio.join-request.decline',
                now()->addDays(30),
                ['userStudio' => $userStudio->id]
            ),
        ]);
    }

    public function accept(Request $request, UserStudio $userStudio): View
    {
        if ($userStudio->status === UserStudio::STATUS_JOIN_PENDING) {
            $userStudio->status = UserStudio::STATUS_ACTIVE;
            $userStudio->save();
        }

        return view('studio.join-request-result', [
            'status' => 'accepted',
            'artistName' => $this->artistName($userStudio),
            'studioName' => $this->studioName($userStudio),
        ]);
    }

    public function decline(Request $request, UserStudio $userStudio): View
    {
        if ($userStudio->status === UserStudio::STATUS_JOIN_PENDING) {
            $userStudio->status = UserStudio::STATUS_INACTIVE;
            $userStudio->payout = false;
            $userStudio->save();
        }

        return view('studio.join-request-result', [
            'status' => 'declined',
            'artistName' => $this->artistName($userStudio),
            'studioName' => $this->studioName($userStudio),
        ]);
    }

    private function artistName(UserStudio $userStudio): string
    {
        $userStudio->loadMissing('user');
        $user = $userStudio->user;
        if (! $user) {
            return 'Artist';
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : ($user->user_name ?? $user->email ?? 'Artist');
    }

    private function studioName(UserStudio $userStudio): string
    {
        $userStudio->loadMissing('studio');

        return trim((string) ($userStudio->studio?->name ?: $userStudio->studio_name ?: 'Studio'));
    }

    private function relationshipLabel(UserStudio $userStudio): ?string
    {
        $labels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective Member',
            'apprentice' => 'Apprentice',
            'other' => 'Other (Contract Artist, Freelancer)',
        ];

        $key = $userStudio->relationship;

        return $key && isset($labels[$key]) ? $labels[$key] : null;
    }
}
