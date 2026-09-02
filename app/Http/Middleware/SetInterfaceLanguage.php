<?php

namespace App\Http\Middleware;

use App\Support\InterfaceLanguage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the signed-in user's language choice to the request.
 *
 * Runs after authentication, because the choice is stored on the account —
 * before that there is nobody to ask, and the login screen simply uses
 * APP_LOCALE.
 */
class SetInterfaceLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        InterfaceLanguage::apply();

        return $next($request);
    }
}
