<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use App\Services\InstagramPortfolioImportService;
use Illuminate\Http\JsonResponse;
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

            return redirect($portfolioUrl)
                ->with('success', $label.' connected successfully.')
                ->with('instagram_auto_import', true);
        } catch (\Throwable $e) {
            Log::error('Instagram OAuth callback failed', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect($portfolioUrl)->with('error', 'Failed to connect Instagram. Please try again.');
        }
    }

    public function import(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        if (blank($user->userDetail?->instagram_access_token)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Connect Instagram first, then import images.',
                ], 422);
            }

            return redirect()
                ->route('portfolio.index')
                ->with('error', 'Connect Instagram first, then import images.');
        }

        try {
            $result = app(InstagramPortfolioImportService::class)->importLatestForUser($user);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => $result['imported'] > 0,
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped'],
                    'message' => $result['message'],
                ]);
            }

            return redirect()
                ->route('portfolio.index')
                ->with(
                    $result['imported'] > 0 ? 'success' : 'error',
                    $result['message']
                );
        } catch (\Throwable $e) {
            Log::error('Instagram portfolio import failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Failed to import Instagram images. Please try again.',
                ], 500);
            }

            return redirect()
                ->route('portfolio.index')
                ->with('error', 'Failed to import Instagram images. Please try again.');
        }
    }

    public function importStart(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (blank($user->userDetail?->instagram_access_token)) {
            return response()->json([
                'ok' => false,
                'total' => 0,
                'skipped' => 0,
                'message' => 'Connect Instagram first, then import images.',
            ], 422);
        }

        try {
            $result = app(InstagramPortfolioImportService::class)->beginAjaxImport($user);

            return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
        } catch (\Throwable $e) {
            Log::error('Instagram import start failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'total' => 0,
                'skipped' => 0,
                'message' => 'Failed to start Instagram import. Please try again.',
            ], 500);
        }
    }

    public function importNext(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (blank($user->userDetail?->instagram_access_token)) {
            return response()->json([
                'ok' => false,
                'done' => true,
                'message' => 'Connect Instagram first, then import images.',
            ], 422);
        }

        try {
            $result = app(InstagramPortfolioImportService::class)->importNextAjax($user);
            $payload = [
                'ok' => (bool) ($result['ok'] ?? false),
                'done' => (bool) ($result['done'] ?? false),
                'current' => (int) ($result['current'] ?? 0),
                'total' => (int) ($result['total'] ?? 0),
                'imported' => (int) ($result['imported'] ?? 0),
                'failed' => (int) ($result['failed'] ?? 0),
                'message' => (string) ($result['message'] ?? ''),
            ];

            if (! empty($result['error'])) {
                $payload['error'] = (string) $result['error'];
            }

            if (! empty($result['portfolio'])) {
                $portfolio = $result['portfolio'];
                $payload['card_html'] = view('artist.portfolio._card', ['portfolio' => $portfolio])->render();
                $payload['editor'] = [
                    'id' => (string) $portfolio->id,
                    'title' => $portfolio->title,
                    'description' => $portfolio->description,
                    'is_active' => (bool) $portfolio->is_active,
                    'primary_style' => $portfolio->primary_style,
                    'other_styles' => $portfolio->other_styles ?? [],
                    'placement' => $portfolio->placement,
                    'color' => $portfolio->color,
                    'tags' => $portfolio->tags ?? [],
                    'image_url' => asset($portfolio->image),
                ];
                $payload['imported_count'] = $user->portfolios()
                    ->whereNotNull('instagram_media_id')
                    ->count();
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            Log::error('Instagram import next failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'done' => true,
                'message' => 'Failed to import Instagram images. Please try again.',
            ], 500);
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        /** @var UserDetail|null $detail */
        $detail = Auth::user()?->userDetail;
        if ($detail) {
            $this->clearInstagramConnection($detail);
        }

        return redirect()
            ->route('portfolio.index')
            ->with('success', 'Instagram disconnected.');
    }

    /**
     * Meta/Instagram deauthorize callback.
     * Called when a user removes the app from their Instagram/Facebook settings.
     */
    public function deauthorize(Request $request): \Illuminate\Http\Response
    {
        $signedRequest = (string) $request->input('signed_request', '');
        if ($signedRequest === '') {
            Log::warning('Instagram deauthorize missing signed_request');

            return response('Missing signed_request', 400);
        }

        $payload = $this->parseSignedRequest($signedRequest);
        if ($payload === null) {
            Log::warning('Instagram deauthorize invalid signed_request');

            return response('Invalid signed_request', 400);
        }

        $instagramUserId = isset($payload['user_id']) ? (string) $payload['user_id'] : '';
        if ($instagramUserId === '') {
            Log::warning('Instagram deauthorize missing user_id', ['payload_keys' => array_keys($payload)]);

            return response('Missing user_id', 400);
        }

        $updated = UserDetail::query()
            ->where('instagram_user_id', $instagramUserId)
            ->get();

        foreach ($updated as $detail) {
            $this->clearInstagramConnection($detail);
        }

        Log::info('Instagram deauthorize processed', [
            'instagram_user_id' => $instagramUserId,
            'cleared_count' => $updated->count(),
        ]);

        return response('OK', 200);
    }

    private function clearInstagramConnection(UserDetail $detail): void
    {
        $detail->update([
            'instagram_user_id' => null,
            'instagram_username' => null,
            'instagram_access_token' => null,
            'instagram_token_expires_at' => null,
            'instagram_connected_at' => null,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseSignedRequest(string $signedRequest): ?array
    {
        $parts = explode('.', $signedRequest, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedSig, $payload] = $parts;
        $secret = trim((string) config('services.instagram.client_secret'));
        if ($secret === '') {
            Log::error('Instagram deauthorize failed: INSTAGRAM_CLIENT_SECRET is not configured');

            return null;
        }

        $sig = $this->base64UrlDecode($encodedSig);
        $expected = hash_hmac('sha256', $payload, $secret, true);
        if ($sig === null || $expected === false || ! hash_equals($expected, $sig)) {
            return null;
        }

        $json = $this->base64UrlDecode($payload);
        if ($json === null) {
            return null;
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    private function base64UrlDecode(string $input): ?string
    {
        $remainder = strlen($input) % 4;
        if ($remainder > 0) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($input, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
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
