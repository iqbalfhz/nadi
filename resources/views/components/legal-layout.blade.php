{{--
    Shell for the two public documents Google requires before an OAuth app may
    be published: a privacy policy and terms of use.

    Self-contained inline CSS, deliberately, for the same reason the error
    pages are: these must render for someone who is not signed in, and for
    Google's own reviewers, without depending on the Vite manifest or a
    session. The palette is copied from errors/layout.blade.php so the two
    public-facing surfaces of NADI look like the same product.
--}}
@props(['title'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('app.name') }}</title>
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
            padding: 2.5rem 1.5rem 4rem;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.7;
        }

        .card {
            width: 100%;
            max-width: 44rem;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), 0 12px 32px -12px rgba(0, 0, 0, 0.15);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--muted);
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
        }

        .brand img { width: 1.5rem; height: 1.5rem; border-radius: 0.375rem; }

        h1 {
            margin: 0 0 0.25rem;
            font-size: 1.625rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .updated {
            margin: 0 0 2rem;
            color: var(--muted);
            font-size: 0.8125rem;
        }

        h2 {
            margin: 2rem 0 0.5rem;
            font-size: 1.0625rem;
            font-weight: 700;
        }

        p, li { font-size: 0.9375rem; color: var(--text); }

        ul { padding-left: 1.15rem; }

        li { margin-bottom: 0.35rem; }

        .lead { color: var(--muted); }

        .callout {
            margin: 1.25rem 0;
            padding: 1rem 1.125rem;
            border-radius: 0.75rem;
            background: var(--accent-soft);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
            font-size: 0.9375rem;
        }

        a { color: var(--accent-strong); }

        .footer {
            margin-top: 2.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 0.8125rem;
        }

        .footer a { margin-right: 1rem; }
    </style>
</head>
<body>
<main class="card">
    <a class="brand" href="{{ url('/') }}">
        <img src="{{ asset('images/nadi-icon.png') }}" alt="">
        {{ config('app.name') }}
    </a>

    <h1>{{ $title }}</h1>
    <p class="updated">Terakhir diperbarui: 1 September 2026</p>

    {{ $slot }}

    <div class="footer">
        <a href="{{ route('legal.privacy') }}">Kebijakan Privasi</a>
        <a href="{{ route('legal.terms') }}">Syarat &amp; Ketentuan</a>
    </div>
</main>
</body>
</html>
