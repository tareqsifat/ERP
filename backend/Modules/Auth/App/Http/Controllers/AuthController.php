<?php

namespace Modules\Auth\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Modules\Auth\App\Http\Requests\LoginRequest;

/**
 * sdd.md §4: the SPA authenticates via Passport's Password Grant, but the
 * grant itself is executed here, server-side, instead of the frontend
 * calling POST /oauth/token directly — that would require shipping the
 * OAuth client_secret inside frontend JS, which defeats the point of it
 * being a secret. Dispatching an in-process sub-request through the same
 * HTTP kernel gets Passport's real Password Grant behaviour (issuing a
 * real oauth_access_tokens/oauth_refresh_tokens row) without a network
 * round-trip and without exposing the secret to the browser.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $tokenResponse = $this->issueTokenViaPasswordGrant(
            $credentials['email'],
            $credentials['password'],
        );

        if (! $tokenResponse) {
            // failed_doc.md §1: generic message — don't reveal whether the
            // email exists (no user-enumeration via the error text).
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $userId = $this->userIdFromAccessToken($tokenResponse['access_token']);
        $user = User::findOrFail($userId);

        if (! $user->is_active) {
            $this->revokeAllTokensFor($user->id);

            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated.'],
            ]);
        }

        return $this->sessionResponse($user, $tokenResponse);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => ['required', 'string']]);

        $tokenResponse = $this->refreshViaPasswordGrant($request->string('refresh_token')->toString());

        if (! $tokenResponse) {
            throw ValidationException::withMessages([
                'refresh_token' => ['This refresh token is invalid or has expired.'],
            ]);
        }

        $userId = $this->userIdFromAccessToken($tokenResponse['access_token']);
        $user = User::findOrFail($userId);

        return $this->sessionResponse($user, $tokenResponse);
    }

    public function logout(Request $request): JsonResponse
    {
        $tokenId = $request->user()?->token()?->id;

        if ($tokenId) {
            // failed_doc.md §1: logout revokes BOTH the access token and
            // its refresh token — a logged-out session cannot silently
            // mint itself a new access token afterwards.
            $this->revokeToken($tokenId);
        }

        return response()->json(['data' => ['message' => 'Logged out.']]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => $this->userPayload($user),
        ]);
    }

    private function sessionResponse(User $user, array $tokenResponse): JsonResponse
    {
        return response()->json([
            'data' => array_merge(
                [
                    'access_token' => $tokenResponse['access_token'],
                    'refresh_token' => $tokenResponse['refresh_token'],
                    'expires_in' => $tokenResponse['expires_in'],
                ],
                $this->userPayload($user),
            ),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'location_id' => $user->location_id,
            ],
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }

    private function issueTokenViaPasswordGrant(string $email, string $password): ?array
    {
        return $this->dispatchOAuthTokenRequest([
            'grant_type' => 'password',
            'client_id' => config('services.passport.password_client_id'),
            'client_secret' => config('services.passport.password_client_secret'),
            'username' => $email,
            'password' => $password,
            'scope' => '',
        ]);
    }

    private function refreshViaPasswordGrant(string $refreshToken): ?array
    {
        return $this->dispatchOAuthTokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('services.passport.password_client_id'),
            'client_secret' => config('services.passport.password_client_secret'),
            'scope' => '',
        ]);
    }

    /**
     * Dispatches an in-process sub-request through Passport's own
     * `/oauth/token` route so we get real League OAuth2 Server behaviour
     * (rate limiting via `throttle`, real token persistence) without a
     * loopback network call.
     */
    private function dispatchOAuthTokenRequest(array $params): ?array
    {
        $subRequest = Request::create('/oauth/token', 'POST', $params);
        $subRequest->headers->set('Accept', 'application/json');

        $response = app()->handle($subRequest);

        if ($response->getStatusCode() !== 200) {
            Log::info('Passport token grant failed', [
                'status' => $response->getStatusCode(),
                'grant_type' => $params['grant_type'],
            ]);

            return null;
        }

        return json_decode($response->getContent(), true);
    }

    /**
     * Passport's access tokens are JWTs (RFC 9068) with a `sub` claim
     * carrying the authenticated user's id. We only need to read that
     * claim here — the token was just minted by our own trusted
     * authorization server a moment ago in the same request, so a full
     * signature re-verification adds nothing; `TokenGuard` still fully
     * verifies the signature on every subsequent authenticated request.
     */
    private function userIdFromAccessToken(string $jwt): ?int
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return isset($payload['sub']) ? (int) $payload['sub'] : null;
    }

    private function revokeAllTokensFor(int $userId): void
    {
        Token::where('user_id', $userId)->update(['revoked' => true]);
    }

    private function revokeToken(string $tokenId): void
    {
        app(TokenRepository::class)->revokeAccessToken($tokenId);
        app(RefreshTokenRepository::class)->revokeRefreshTokensByAccessTokenId($tokenId);
    }
}
