<?php

namespace App\Support;

use App\Models\Studio;
use App\Models\UserStudio;
use Illuminate\Support\Collection;

class StudioPaymentLinkModalData
{
    /**
     * @return Collection<int, array{user_id:int,name:string,first_name:string,styles:string,option_label:string,pay_note:string}>
     */
    public static function artists(Studio $studio): Collection
    {
        $studioName = trim((string) ($studio->name ?? '')) ?: 'the studio';

        return UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->orderBy('id')
            ->get()
            ->map(function (UserStudio $link) use ($studioName) {
                $artist = $link->user;
                if (! $artist || (string) ($artist->role ?? '') !== 'artist') {
                    return null;
                }
                $detail = $artist->userDetail;
                $name = $detail?->publicDisplayName();
                if (! $name) {
                    $name = trim(($artist->first_name ?? '').' '.($artist->last_name ?? '')) ?: 'Artist';
                }
                $firstName = trim((string) ($artist->first_name ?? '')) ?: explode(' ', $name)[0];
                $styles = self::formatTattooStyles($detail?->tattoo_styles ?? null);
                $relLabel = self::relationshipLabel($link->relationship);
                $split = $link->revenue_split;
                $payoutType = $split !== null ? 'Revenue split' : 'Workstation rent';
                $meta = trim(implode(' · ', array_filter([$relLabel, $payoutType])));
                $optionLabel = $meta !== '' ? $name.' · '.$meta : ($styles !== '' ? $name.' · '.$styles : $name);

                if ($split !== null) {
                    $artistPct = max(0, min(100, (int) $split));
                    $studioPct = 100 - $artistPct;
                    $payNote = 'Each payment is split: '.$firstName.' '.$artistPct.'%, '.$studioName.' '.$studioPct.'%.';
                } else {
                    $payNote = $firstName.' gets the full payment. Nothing is split.';
                }

                return [
                    'user_id' => (int) $artist->id,
                    'name' => $name,
                    'first_name' => $firstName,
                    'styles' => $styles,
                    'option_label' => $optionLabel,
                    'pay_note' => $payNote,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $artists
     * @return array{
     *     canCreatePaymentLinks: bool,
     *     paymentLinksBlockedMessage: ?string,
     *     paymentLinksBlockedActionUrl: ?string,
     *     paymentLinksBlockedActionLabel: ?string
     * }
     */
    public static function createGate(bool $profileComplete, ?Studio $studio, Collection $artists): array
    {
        $hasArtists = $artists->isNotEmpty();
        $canCreate = $profileComplete && $hasArtists && $studio !== null;

        $message = null;
        $actionUrl = null;
        $actionLabel = null;

        if (! $canCreate) {
            if (! $profileComplete) {
                $message = 'Complete your studio profile before creating bookings.';
                $actionUrl = route('studio.onboarding.profile');
                $actionLabel = 'Complete profile';
            } elseif (! $hasArtists) {
                $message = 'Invite or add an artist before creating a booking payment link.';
                $actionUrl = route('studio.artists');
                $actionLabel = 'Go to Artists';
            } else {
                $message = 'Set up your studio before creating booking payment links.';
            }
        }

        return [
            'canCreatePaymentLinks' => $canCreate,
            'paymentLinksBlockedMessage' => $message,
            'paymentLinksBlockedActionUrl' => $actionUrl,
            'paymentLinksBlockedActionLabel' => $actionLabel,
        ];
    }

    private static function relationshipLabel(?string $relationship): string
    {
        return match ($relationship) {
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
            default => '',
        };
    }

    private static function formatTattooStyles(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }

        $labels = [];
        if (array_key_exists('primary_style', $payload) || array_key_exists('other_styles', $payload)) {
            $primary = trim((string) ($payload['primary_style'] ?? ''));
            if ($primary !== '') {
                $labels[] = $primary;
            }
            foreach ((array) ($payload['other_styles'] ?? []) as $style) {
                if (! is_scalar($style)) {
                    continue;
                }
                $style = trim((string) $style);
                if ($style !== '') {
                    $labels[] = $style;
                }
            }
        } else {
            foreach ($payload as $style) {
                if (! is_scalar($style)) {
                    continue;
                }
                $style = trim((string) $style);
                if ($style !== '') {
                    $labels[] = $style;
                }
            }
        }

        return implode(', ', array_slice(array_values(array_unique($labels)), 0, 3));
    }
}
