<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses anyone who holds a valid token but has no business in the mobile
 * app — the API's answer to Filament's canAccessPanel().
 *
 * Holding a token is not the same as belonging here. A cashier or a queue
 * operator has every right to sign in to /app and none of the field modules
 * the phone carries; without this they would reach an app with an empty home
 * screen and a set of endpoints that answer 403 one at a time.
 */
class EnsureMobileAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->canUseMobileApp()) {
            return response()->json([
                'message' => __('Akun Anda tidak punya akses ke aplikasi ini. Hubungi admin.'),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
