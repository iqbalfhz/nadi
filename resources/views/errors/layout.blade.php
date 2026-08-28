{{--
    Shared shell for every error page. Deliberately self-contained — inline
    CSS, no @vite, no Blade components, no queries: this page is what renders
    when something is already broken, so it must not depend on the build
    manifest, the database, or the session store to display.
--}}
@php
    use App\Support\ErrorPageDestination;
    use App\Support\SocialLinks;

    $destination = ErrorPageDestination::resolve();
@endphp
    <!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/nadi-icon.png') }}">
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
            --accent: #f59e0b;
            --accent-strong: #b45309;
            --accent-soft: #fef3c7;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b0f19;
                --card: #131824;
                --border: rgba(255, 255, 255, 0.08);
                --text: #f3f4f6;
                --muted: #9ca3af;
                --accent: #f59e0b;
                --accent-strong: #fbbf24;
                --accent-soft: rgba(245, 158, 11, 0.12);
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
        }

        .card {
            width: 100%;
            max-width: 34rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), 0 12px 32px -12px rgba(0, 0, 0, 0.15);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.75rem;
            color: var(--muted);
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .brand img { width: 1.5rem; height: 1.5rem; border-radius: 0.375rem; }

        .code {
            display: inline-block;
            padding: 0.25rem 0.875rem;
            border-radius: 9999px;
            background: var(--accent-soft);
            color: var(--accent-strong);
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        h1 {
            margin: 1rem 0 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        p.message {
            margin: 0 auto;
            max-width: 26rem;
            color: var(--muted);
            font-size: 0.9375rem;
        }

        .actions {
            margin-top: 1.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.625rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.625rem 1.125rem;
            border: 1px solid transparent;
            border-radius: 0.625rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            transition: filter 0.15s ease, background-color 0.15s ease;
        }

        .btn-primary { background: var(--accent); color: #1f1300; }
        .btn-primary:hover { filter: brightness(1.07); }

        .btn-secondary {
            background: transparent;
            border-color: var(--border);
            color: var(--muted);
        }

        .btn-secondary:hover { color: var(--text); }

        footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.625rem;
        }

        .credit { color: var(--muted); font-size: 0.75rem; }

        .socials { display: flex; gap: 0.5rem; }

        .socials a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            border: 1px solid var(--border);
            color: var(--muted);
            transition: color 0.15s ease, border-color 0.15s ease;
        }

        .socials a:hover { color: var(--accent-strong); border-color: var(--accent); }

        .socials svg { width: 1rem; height: 1rem; }
    </style>
</head>
<body>
<main class="card">
    <div class="brand">
        <img src="{{ asset('images/nadi-icon.png') }}" alt="">
        {{ config('app.name') }}
    </div>

    <div>
        <span class="code">Error {{ $code }}</span>
        <h1>{{ $title }}</h1>
        <p class="message">{{ $message }}</p>
    </div>

    <div class="actions">
        {{-- Signing out is the only useful action left for an account that
             can reach neither panel, and Fortify only accepts it as a POST. --}}
        @if ($destination['method'] === 'post')
            <form method="POST" action="{{ $destination['url'] }}">
                @csrf
                <button type="submit" class="btn btn-primary">{{ $destination['label'] }}</button>
            </form>
        @else
            <a class="btn btn-primary" href="{{ $destination['url'] }}">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" style="width:1rem;height:1rem">
                    <path d="M12.5 15L7.5 10L12.5 5"/>
                </svg>
                {{ $destination['label'] }}
            </a>
        @endif

        @isset($retry)
            <button class="btn btn-secondary" type="button" onclick="window.location.reload()">Muat Ulang</button>
        @endisset
    </div>

    <footer>
        <span class="credit">{{ config('app.name') }} — dibuat oleh {{ \App\Support\SocialLinks::AUTHOR }}</span>

        <div class="socials">
            <a href="{{ \App\Support\SocialLinks::INSTAGRAM }}" target="_blank" rel="noopener noreferrer"
               title="Instagram" aria-label="Instagram">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="20" rx="5"/>
                    <circle cx="12" cy="12" r="4"/>
                    <circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/>
                </svg>
            </a>
            <a href="{{ \App\Support\SocialLinks::GITHUB }}" target="_blank" rel="noopener noreferrer"
               title="GitHub" aria-label="GitHub">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2a10 10 0 0 0-3.16 19.49c.5.09.68-.22.68-.48v-1.7c-2.78.6-3.37-1.34-3.37-1.34-.45-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.6.07-.6 1 .07 1.53 1.03 1.53 1.03.9 1.53 2.34 1.09 2.91.83.09-.65.35-1.09.63-1.34-2.22-.25-4.56-1.11-4.56-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.65 0 0 .84-.27 2.75 1.02a9.5 9.5 0 0 1 5 0c1.91-1.29 2.75-1.02 2.75-1.02.55 1.38.2 2.4.1 2.65.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.69-4.57 4.94.36.31.68.92.68 1.85v2.74c0 .27.18.58.69.48A10 10 0 0 0 12 2Z"/>
                </svg>
            </a>
        </div>
    </footer>
</main>
</body>
</html>
