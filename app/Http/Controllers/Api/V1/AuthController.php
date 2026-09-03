<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signing a phone in.
 *
 * The web signs in through Fortify, which leans on the session for two things
 * the API cannot have: the login throttle and the pending-2FA state between
 * the password step and the code step. Both are rebuilt here rather than
 * skipped — an API that quietly drops the account's second factor is worse
 * than one that never offered it.
 */
class AuthController extends Controller
{
    /**
     * Matches Fortify's own limit (FortifyServiceProvider::configureRateLimiting)
     * so the phone and the browser are held to one standard.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Long enough to type a code off a phone screen, short enough that a
     * stolen half-login is worthless by the time anyone finds it.
     */
    private const CHALLENGE_MINUTES = 5;

    public const CHALLENGE_ABILITY = 'two-factor-challenge';

    public const MOBILE_ABILITY = 'mobile';

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $this->assertNotThrottled($request, $credentials['email']);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user instanceof User || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($this->throttleKey($request, $credentials['email']));

            throw ValidationException::withMessages([
                'email' => __('Email atau password salah.'),
            ]);
        }

        // Mirrors PreventInactiveUserLogin, which the web relies on. A
        // deactivated account must not get a token even with the right
        // password.
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => __('Akun ini sudah dinonaktifkan. Hubungi admin.'),
            ]);
        }

        if (! $user->canUseMobileApp()) {
            throw ValidationException::withMessages([
                'email' => __('Akun Anda tidak punya akses ke aplikasi ini. Hubungi admin.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, $credentials['email']));

        if ($user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null) {
            return $this->challenge($user, $credentials['device_name']);
        }

        return $this->issueToken($user, $credentials['device_name']);
    }

    /**
     * Step two of a two-factor sign-in.
     *
     * The pending state rides on a short-lived token with a single ability
     * instead of a session key. That reuses the token table rather than
     * inventing a second store, and the ability is what stops a half-finished
     * login from reaching any real endpoint.
     */
    public function twoFactorChallenge(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->assertNotThrottled($request, 'two-factor:'.$user->id);

        if (isset($data['recovery_code'])) {
            return $this->completeWithRecoveryCode($request, $user, $data['recovery_code'], $data['device_name']);
        }

        if (! isset($data['code']) || ! $provider->verify(decrypt($user->two_factor_secret), $data['code'])) {
            RateLimiter::hit($this->throttleKey($request, 'two-factor:'.$user->id));

            throw ValidationException::withMessages([
                'code' => __('Kode autentikasi tidak cocok.'),
            ]);
        }

        return $this->completeChallenge($request, $user, $data['device_name']);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();

        $token->delete();

        return response()->json([
            'message' => __('Anda sudah keluar dari aplikasi.'),
        ]);
    }

    private function challenge(User $user, string $deviceName): JsonResponse
    {
        $token = $user->createToken(
            $deviceName.' (tantangan)',
            [self::CHALLENGE_ABILITY],
            now()->addMinutes(self::CHALLENGE_MINUTES),
        );

        return response()->json([
            'two_factor' => true,
            'challenge_token' => $token->plainTextToken,
        ]);
    }

    private function completeWithRecoveryCode(Request $request, User $user, string $code, string $deviceName): JsonResponse
    {
        $decoded = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $codes = is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];

        if (! in_array($code, $codes, true)) {
            RateLimiter::hit($this->throttleKey($request, 'two-factor:'.$user->id));

            throw ValidationException::withMessages([
                'recovery_code' => __('Kode pemulihan tidak cocok.'),
            ]);
        }

        // Burn it. A recovery code that survives its own use is a password
        // written on the back of the phone it protects.
        $remaining = array_values(array_filter(
            $codes,
            fn (string $stored): bool => $stored !== $code,
        ));

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($remaining)),
        ])->save();

        return $this->completeChallenge($request, $user, $deviceName);
    }

    private function completeChallenge(Request $request, User $user, string $deviceName): JsonResponse
    {
        RateLimiter::clear($this->throttleKey($request, 'two-factor:'.$user->id));

        // The challenge token has done its job, and leaving it alive would
        // let the same half-login be completed twice.
        /** @var PersonalAccessToken $challengeToken */
        $challengeToken = $request->user()->currentAccessToken();

        $challengeToken->delete();

        return $this->issueToken($user, $deviceName);
    }

    private function issueToken(User $user, string $deviceName): JsonResponse
    {
        $token = $user->createToken($deviceName, [self::MOBILE_ABILITY]);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    private function assertNotThrottled(Request $request, string $identifier): void
    {
        $key = $this->throttleKey($request, $identifier);

        if (! RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('Terlalu banyak percobaan. Coba lagi dalam :detik detik.', [
                'detik' => RateLimiter::availableIn($key),
            ]),
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }

    private function throttleKey(Request $request, string $identifier): string
    {
        return Str::transliterate(Str::lower($identifier).'|'.$request->ip());
    }
}
