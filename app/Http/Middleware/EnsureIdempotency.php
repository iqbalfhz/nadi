<?php

namespace App\Http\Middleware;

use App\Models\ApiIdempotencyKey;
use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes every write safe to retry.
 *
 * A phone filing a report from a basement sends it when signal returns, and
 * the reply can be lost on the way back — leaving the device unable to tell
 * "saved" from "never arrived". Both guesses are bad: retrying blind
 * duplicates the report, giving up loses it. So the client stamps each
 * submission with a UUID and this remembers what that UUID already produced.
 *
 * The header is required rather than optional. There is exactly one consumer,
 * so making the safe path the only path costs nothing and removes a way for
 * the app to be subtly wrong.
 */
class EnsureIdempotency
{
    /**
     * A UUID is 36 characters; the column holds 64. Anything longer is a
     * client that has misunderstood the contract, and truncating it silently
     * would make two different keys collide.
     */
    private const MAX_KEY_LENGTH = 64;

    /**
     * Written while the request is in flight, so a simultaneous retry can be
     * told "still working" instead of being handed an empty response body.
     */
    private const IN_FLIGHT = 0;

    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($key === '' || strlen($key) > self::MAX_KEY_LENGTH) {
            return response()->json([
                'message' => __('Permintaan ini tidak menyertakan kunci idempotensi yang sah.'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $endpoint = $request->method().' '.$request->path();

        $existing = ApiIdempotencyKey::query()
            ->where('user_id', $user->id)
            ->where('key', $key)
            ->first();

        if ($existing !== null) {
            return $this->replay($existing, $endpoint);
        }

        // Claim the key before doing the work, not after: two retries arriving
        // together must not both run the action and both succeed. The unique
        // index is what actually serialises them.
        try {
            $claim = ApiIdempotencyKey::create([
                'user_id' => $user->id,
                'key' => $key,
                'endpoint' => $endpoint,
                'status' => self::IN_FLIGHT,
                'response' => [],
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->stillWorking();
        }

        $response = $next($request);

        $this->remember($claim, $response);

        return $response;
    }

    /**
     * Hand back exactly what the first attempt produced — the same body, not
     * a reconstruction of it.
     */
    private function replay(ApiIdempotencyKey $existing, string $endpoint): Response
    {
        // The same key against a different action is a client bug. Answering
        // with the unrelated first response would hide it; 409 says so.
        if ($existing->endpoint !== $endpoint) {
            return response()->json([
                'message' => __('Kunci idempotensi ini sudah dipakai untuk permintaan lain.'),
            ], Response::HTTP_CONFLICT);
        }

        if ($existing->status === self::IN_FLIGHT) {
            return $this->stillWorking();
        }

        return response()
            ->json($existing->response, $existing->status)
            ->header('Idempotency-Replayed', 'true');
    }

    private function stillWorking(): Response
    {
        return response()->json([
            'message' => __('Permintaan yang sama sedang diproses. Coba lagi sebentar.'),
        ], Response::HTTP_CONFLICT);
    }

    /**
     * Only successes are remembered. A rejected payload should be fixable and
     * re-sendable under the same key — the client has one report in hand and
     * one key for it, and forcing a new key after a validation error would
     * mean the outbox has to invent one, which is where duplicates come from.
     */
    private function remember(ApiIdempotencyKey $claim, Response $response): void
    {
        if (! $response->isSuccessful()) {
            $claim->delete();

            return;
        }

        $decoded = json_decode((string) $response->getContent(), true);

        $claim->update([
            'status' => $response->getStatusCode(),
            'response' => is_array($decoded) ? $decoded : [],
        ]);
    }
}
