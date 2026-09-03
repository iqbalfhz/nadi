<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureKioskIsUnlocked;
use App\Http\Middleware\LogDeniedAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'kiosk.unlocked' => EnsureKioskIsUnlocked::class,
            // Sanctum ships these but registers no aliases of its own. The
            // mobile API leans on 'ability' to keep a two-factor challenge
            // token away from every endpoint except the challenge itself.
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        // Applied to every response, panels and public screens alike.
        $middleware->append(AddSecurityHeaders::class);
        $middleware->append(LogDeniedAccess::class);

        // Cloudflare Tunnel + Coolify terminate TLS at the edge and proxy
        // plain HTTP to this container over a host-only port (not directly
        // internet-reachable), so trusting every hop is safe. Without this,
        // Laravel never reads X-Forwarded-Proto, sees requests as http://
        // internally, and rejects signed URLs (e.g. Livewire file uploads)
        // generated as https:// since the signature no longer matches.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Laravel answers the API in English by default — "Unauthenticated.",
        // "This action is unauthorized.", "Server Error". NADI.MD §5.11 says
        // raw technical text never reaches a screen, and a phone has no
        // developer console to explain it away. Each failure gets a sentence
        // its reader can act on; the detail goes to the log instead.
        //
        // Validation is left alone: its shape is Laravel's own and its
        // wording already comes from lang/id.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') || $e instanceof ValidationException) {
                return null;
            }

            $status = match (true) {
                $e instanceof AuthenticationException => 401,
                $e instanceof AuthorizationException => 403,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            $messages = [
                401 => 'Sesi Anda sudah berakhir. Masuk kembali.',
                403 => 'Anda tidak punya akses ke bagian ini.',
                404 => 'Data yang diminta tidak ditemukan.',
                405 => 'Permintaan ini tidak dikenali.',
                413 => 'Berkas yang dikirim terlalu besar.',
                429 => 'Terlalu banyak permintaan. Coba lagi sebentar.',
            ];

            // Nothing above 500 is expected, so it is worth a log entry even
            // though the phone is told something reassuring.
            if ($status >= 500) {
                report($e);
            }

            return response()->json([
                'message' => __($messages[$status] ?? 'Terjadi kesalahan di server. Coba lagi nanti.'),
            ], $status);
        });
    })->create();
