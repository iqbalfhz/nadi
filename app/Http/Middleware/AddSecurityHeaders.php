<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline response headers Laravel doesn't send on its own.
 *
 * Deliberately no Content-Security-Policy here: Filament and Livewire both
 * rely on inline scripts and styles, so a CSP strict enough to be worth
 * having would need nonces threaded through every one of them — that is its
 * own project, and a half-strict policy buys nothing while breaking panels.
 * These three are unconditional wins with no such tradeoff.
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Nothing in NADI is meant to be embedded anywhere, so refuse to be
        // framed at all — that closes clickjacking against the admin panel,
        // where a single disguised click can delete records.
        $response->headers->set('X-Frame-Options', 'DENY');

        // Stops a browser from second-guessing a declared Content-Type, which
        // is how an uploaded "image" ends up executed as something else.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Don't hand this office's internal URLs — which spell out module and
        // record ids — to whatever external site someone clicks through to.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
