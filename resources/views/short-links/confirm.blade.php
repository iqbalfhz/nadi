{{--
    Shown before following a short link to a destination that isn't on the
    trusted list (config/short-links.php). Self-contained inline CSS for the
    same reason as the error pages: this is a public page with no session and
    no panel around it.
--}}
@php use App\Support\SocialLinks; @endphp
    <!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    {{-- Don't leak this office's domain to the destination as a referrer. --}}
    <meta name="referrer" content="no-referrer">
    <title>Membuka tautan luar | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/nadi-icon.png') }}">
    <style>
        :root {
            --bg: #f8fafc; --card: #fff; --border: #e5e7eb; --text: #111827;
            --muted: #6b7280; --accent: #f59e0b; --accent-strong: #b45309;
            --warn-bg: #fffbeb; --warn-border: #fde68a;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b0f19; --card: #131824; --border: rgba(255,255,255,.08);
                --text: #f3f4f6; --muted: #9ca3af; --accent: #f59e0b;
                --accent-strong: #fbbf24; --warn-bg: rgba(245,158,11,.08);
                --warn-border: rgba(245,158,11,.3);
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center;
            justify-content: center; padding: 1.5rem; background: var(--bg);
            color: var(--text); line-height: 1.6;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .card {
            width: 100%; max-width: 34rem; background: var(--card);
            border: 1px solid var(--border); border-radius: 1rem;
            padding: 2.25rem 2rem; text-align: center;
            box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 12px 32px -12px rgba(0,0,0,.15);
        }

        .brand {
            display: inline-flex; align-items: center; gap: .5rem;
            margin-bottom: 1.5rem; color: var(--muted); font-size: .8125rem;
            font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
        }

        .brand img { width: 1.5rem; height: 1.5rem; border-radius: .375rem; }

        h1 { margin: 0 0 .5rem; font-size: 1.375rem; font-weight: 700; }

        p.lead { margin: 0 auto 1.25rem; max-width: 27rem; color: var(--muted); font-size: .9375rem; }

        .target {
            margin: 0 0 1.5rem; padding: .875rem 1rem; border-radius: .625rem;
            background: var(--warn-bg); border: 1px solid var(--warn-border);
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .8125rem; word-break: break-all; text-align: left;
        }

        .actions { display: flex; flex-wrap: wrap; gap: .625rem; justify-content: center; }

        .btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .625rem 1.125rem; border: 1px solid transparent;
            border-radius: .625rem; font-size: .875rem; font-weight: 600;
            text-decoration: none; cursor: pointer; font-family: inherit;
        }

        .btn-primary { background: var(--accent); color: #1f1300; }
        .btn-primary:hover { filter: brightness(1.07); }
        .btn-secondary { background: transparent; border-color: var(--border); color: var(--muted); }
        .btn-secondary:hover { color: var(--text); }

        footer {
            margin-top: 1.75rem; padding-top: 1rem; border-top: 1px solid var(--border);
            color: var(--muted); font-size: .75rem;
        }
    </style>
</head>
<body>
<main class="card">
    <div class="brand">
        <img src="{{ asset('images/nadi-icon.png') }}" alt="">
        {{ config('app.name') }}
    </div>

    <h1>Anda akan meninggalkan {{ config('app.name') }}</h1>
    <p class="lead">
        Tautan pendek ini mengarah ke alamat di luar {{ config('app.name') }}. Periksa dulu alamat
        tujuannya di bawah — jangan lanjutkan kalau Anda tidak mengenalinya, apalagi kalau
        halaman itu meminta Anda memasukkan password.
    </p>

    <p class="target">{{ $shortLink->target_url }}</p>

    <div class="actions">
        <a class="btn btn-primary"
           href="{{ route('short-links.redirect', ['code' => $shortLink->code, 'lanjut' => 1]) }}"
           rel="noopener noreferrer nofollow">
            Lanjutkan
        </a>
        <a class="btn btn-secondary" href="{{ url('/') }}">Batal</a>
    </div>

    <footer>{{ config('app.name') }} — dibuat oleh {{ SocialLinks::AUTHOR }}</footer>
</main>
</body>
</html>
