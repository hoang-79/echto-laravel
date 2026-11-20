{{--
    Seiten für Besucher der Website (Bestätigung per E-Mail, Code bei der Anmeldung). Sie gehören zur Website der
    Kundin, nicht zu Echto: schlichte Systemschrift, keine Marke. Anpassen mit `php artisan vendor:publish
    --tag=echto-views` (dann unter resources/views/vendor/echto/visitor).
--}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <style>
        :root { --text: #1f2933; --muted: #52606d; --line: #cbd2d9; --page: #f5f7fa; --card: #fff; --accent: #1f6f50; --on-accent: #fff; --danger: #a8261b; }
        @media (prefers-color-scheme: dark) { :root { --text: #f5f7fa; --muted: #9aa5b1; --line: #3e4c59; --page: #1f2933; --card: #323f4b; --accent: #7fd1ae; --on-accent: #1f2933; --danger: #f29a90; } }
        * { box-sizing: border-box; }
        body { margin: 0; font: 17px/1.55 system-ui, -apple-system, 'Segoe UI', sans-serif; color: var(--text); background: var(--page); overflow-wrap: anywhere; }
        main { max-width: 520px; margin: 0 auto; padding: 56px 20px; display: grid; gap: 16px; }
        h1 { font-size: 26px; line-height: 1.25; margin: 0; }
        p { margin: 0; }
        .muted { color: var(--muted); }
        .card { padding: 24px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); display: grid; gap: 16px; }
        button, .button { display: inline-flex; justify-content: center; align-items: center; min-height: 44px; padding: 10px 20px; border: 1px solid var(--accent); border-radius: 6px; background: var(--accent); color: var(--on-accent); font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .button.secondary { background: var(--card); color: var(--text); border-color: var(--muted); }
        input { width: 100%; min-height: 48px; padding: 10px 12px; border: 1px solid var(--muted); border-radius: 6px; background: var(--card); color: var(--text); font: 600 24px/1 ui-monospace, Menlo, monospace; letter-spacing: 6px; text-align: center; }
        label { font-weight: 600; }
        .error { color: var(--danger); font-weight: 600; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
    </style>
</head>
<body>
<main>
    @yield('content')
</main>
</body>
</html>
