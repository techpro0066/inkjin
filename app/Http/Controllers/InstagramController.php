<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InstagramController extends Controller
{
    private const STATE_SESSION_KEY = 'instagram_oauth_state';

    private function redirectUri(): string
    {
        $configured = trim((string) config('services.instagram.redirect'));

        if ($configured !== '' && Str::startsWith($configured, ['http://', 'https://'])) {
            return $configured;
        }

        return route('instagram.callback');
    }

    public function connect(Request $request): RedirectResponse
    {
        $clientId = trim((string) config('services.instagram.client_id'));
        $clientSecret = trim((string) config('services.instagram.client_secret'));
        $redirectUri = $this->redirectUri();

        if ($clientId === '' || $clientSecret === '') {
            return redirect()
                ->route('portfolio.index')
                ->with('error', 'Instagram is not configured yet. Add INSTAGRAM_CLIENT_ID and INSTAGRAM_CLIENT_SECRET to your .env (use the Instagram App ID/Secret from the Instagram product, not the Facebook App ID).');
        }

        $state = Str::random(40);
        session([self::STATE_SESSION_KEY => $state]);

        $scopes = implode(',', config('services.instagram.scopes', ['instagram_business_basic']));

        $authUrl = 'https://www.instagram.com/oauth/authorize?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scopes,
            'state' => $state,
        ]);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $portfolioUrl = route('portfolio.index');

        if ($request->filled('error')) {
            return redirect($portfolioUrl)->with(
                'error',
                'Instagram connection was cancelled or denied.'
            );
        }

        $expectedState = session()->pull(self::STATE_SESSION_KEY);
        if (! $expectedState || ! hash_equals((string) $expectedState, (string) $request->query('state', ''))) {
            return redirect($portfolioUrl)->with('error', 'Invalid Instagram OAuth state. Please try again.');
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return redirect($portfolioUrl)->with('error', 'Instagram did not return an authorization code.');
        }

        try {
            $shortLived = $this->exchangeCodeForShortLivedToken($code);
            $accessToken = (string) ($shortLived['access_token'] ?? '');
            $userId = isset($shortLived['user_id']) ? (string) $shortLived['user_id'] : null;

            if ($accessToken === '') {
                throw new \RuntimeException('Missing access_token from Instagram.');
            }

            $longLived = $this->exchangeForLongLivedToken($accessToken);
            $token = (string) ($longLived['access_token'] ?? $accessToken);
            $expiresIn = (int) ($longLived['expires_in'] ?? 0);

            $profile = $this->fetchProfile($token);
            $username = $profile['username'] ?? null;
            $userId = isset($profile['user_id']) ? (string) $profile['user_id'] : ($profile['id'] ?? $userId);

            /** @var UserDetail|null $detail */
            $detail = Auth::user()?->userDetail;
            if (! $detail) {
                return redirect($portfolioUrl)->with('error', 'Artist profile not found.');
            }

            $detail->update([
                'instagram_user_id' => $userId,
                'instagram_username' => $username,
                'instagram_access_token' => $token,
                'instagram_token_expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
                'instagram_connected_at' => now(),
            ]);

            $label = $username ? '@'.$username : 'Instagram';

            return redirect($portfolioUrl)->with('success', $label.' connected successfully.');
        } catch (\Throwable $e) {
            Log::error('Instagram OAuth callback failed', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect($portfolioUrl)->with('error', 'Failed to connect Instagram. Please try again.');
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        /** @var UserDetail|null $detail */
        $detail = Auth::user()?->userDetail;
        if ($detail) {
            $detail->update([
                'instagram_user_id' => null,
                'instagram_username' => null,
                'instagram_access_token' => null,
                'instagram_token_expires_at' => null,
                'instagram_connected_at' => null,
            ]);
        }

        return redirect()
            ->route('portfolio.index')
            ->with('success', 'Instagram disconnected.');
    }

    /**
     * @return array<string, mixed>
     */
    private function exchangeCodeForShortLivedToken(string $code): array
    {
        $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => config('services.instagram.client_id'),
            'client_secret' => config('services.instagram.client_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri(),
            'code' => $code,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Token exchange failed: '.$response->body());
        }

        $json = $response->json();

        // Instagram sometimes wraps data in a "data" array.
        if (isset($json['data'][0]) && is_array($json['data'][0])) {
            return $json['data'][0];
        }

        return is_array($json) ? $json : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $response = Http::get('https://graph.instagram.com/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => config('services.instagram.client_secret'),
            'access_token' => $shortLivedToken,
        ]);

        if (! $response->successful()) {
            Log::warning('Instagram long-lived token exchange failed; keeping short-lived token', [
                'body' => $response->body(),
            ]);

            return ['access_token' => $shortLivedToken];
        }

        return $response->json() ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchProfile(string $accessToken): array
    {
        $response = Http::get('https://graph.instagram.com/v21.0/me', [
            'fields' => 'user_id,username',
            'access_token' => $accessToken,
        ]);

        if (! $response->successful()) {
            // Fallback field set used by some Graph versions.
            $response = Http::get('https://graph.instagram.com/me', [
                'fields' => 'id,username',
                'access_token' => $accessToken,
            ]);
        }

        if (! $response->successful()) {
            Log::warning('Instagram profile fetch failed', ['body' => $response->body()]);

            return [];
        }

        return $response->json() ?: [];
    }
}
