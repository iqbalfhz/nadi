<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureKioskIsUnlocked;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'kiosk.unlocked' => EnsureKioskIsUnlocked::class,
        ]);

        // Applied to every response, panels and public screens alike.
        $middleware->append(AddSecurityHeaders::class);

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
    })->create();
