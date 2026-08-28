<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records requests that were refused — the one audit category that only
 * shows up when someone is somewhere they shouldn't be.
 *
 * Deliberately hooked on the *response status*, not on Gate checks. Filament
 * calls the gate constantly just to decide which menu items and buttons to
 * draw, and almost all of those legitimately come back false; logging them
 * would bury the log in thousands of lines a day that mean nothing. A 403
 * response means a person actually asked for something and was told no.
 *
 * Guests never reach this: an unauthenticated request to a panel is
 * redirected to the login page (302), so everything here is a signed-in user
 * probing past their own permissions.
 */
class LogDeniedAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== Response::HTTP_FORBIDDEN) {
            return $response;
        }

        if ($request->user() === null) {
            return $response;
        }

        activity('ditolak')
            ->causedBy($request->user())
            ->withProperty('halaman', $request->path())
            ->withProperty('metode', $request->method())
            ->log('Akses ditolak');

        return $response;
    }
}
