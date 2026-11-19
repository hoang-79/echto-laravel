<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <style>
        :root { --text: #16211c; --muted: #5b665f; --line: #d7ddd8; --page: #f5f4ef; --card: #fff; --accent: #14734a; --on-accent: #fff; --warn: #8a5a00; --warn-soft: #fbefd5; --ok-soft: #e3f3ea; --danger: #a8261b; }
        @media (prefers-color-scheme: dark) {
            :root { --text: #f5f4ef; --muted: #a7b2ab; --line: #26342d; --page: #16211c; --card: #26342d; --accent: #5fd39a; --on-accent: #16211c; --warn: #f2c15a; --warn-soft: #3a3320; --ok-soft: #1f3a2d; --danger: #f29a90; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; font: 16px/1.5 'IBM Plex Sans', system-ui, sans-serif; color: var(--text); background: var(--page); }
        header { display: flex; flex-wrap: wrap; gap: 4px 16px; align-items: baseline; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid var(--line); background: var(--card); }
        header strong { font-size: 18px; }
        header span { color: var(--muted); font-size: 14px; }
        main { max-width: 860px; margin: 0 auto; padding: 28px 20px 56px; }
        h1 { font-size: 23px; line-height: 1.25; margin: 0; }
        p { margin: 0; }
        a { color: var(--accent); font-weight: 500; }
        .muted { color: var(--muted); }
        .small { font-size: 14px; }
        .list { margin: 20px 0 0; padding: 0; list-style: none; border: 1px solid var(--line); border-radius: 10px; background: var(--card); }
        .list > li { display: flex; flex-wrap: wrap; gap: 12px 20px; justify-content: space-between; padding: 16px 20px; border-top: 1px solid var(--line); }
        .list > li:first-child { border-top: 0; }
        .list > li > div:first-child { flex: 1 1 320px; min-width: 0; overflow-wrap: anywhere; }
        .reason { margin-top: 8px; font-weight: 500; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-start; }
        button { min-height: 44px; padding: 10px 18px; border: 1px solid var(--accent); border-radius: 6px; background: var(--accent); color: var(--on-accent); font: inherit; font-weight: 600; cursor: pointer; }
        button.secondary { border-color: var(--muted); background: var(--card); color: var(--text); }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .card { margin-top: 20px; padding: 20px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); }
        .notice { display: flex; gap: 12px; margin-top: 16px; padding: 14px 18px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); }
        .notice.warn { border-color: var(--warn); background: var(--warn-soft); }
        .notice.ok { border-color: var(--accent); background: var(--ok-soft); }
        .notice.error { border-color: var(--danger); }
        .notice strong { display: block; }
        dl { display: grid; grid-template-columns: minmax(90px, auto) 1fr; gap: 10px 20px; margin: 0; }
        dt { color: var(--muted); }
        dd { margin: 0; white-space: pre-wrap; overflow-wrap: anywhere; }
        .empty { margin: 56px auto; max-width: 440px; text-align: center; }
        .empty svg { width: 56px; height: 56px; color: var(--accent); }
        nav[aria-label] { margin-top: 20px; }
        @media (max-width: 520px) { dl { grid-template-columns: 1fr; gap: 2px; } dd { margin-bottom: 10px; } }
    </style>
</head>
<body>
<header>
    <strong>{{ __('Zurückgehaltene Einsendungen') }}</strong>
    <span>{{ __('geschützt mit Echto') }}</span>
</header>
<main>
    @if (session('echto.status'))
        <div class="notice ok" role="status"><div>{{ session('echto.status') }}</div></div>
    @endif
    @if (session('echto.error'))
        <div class="notice error" role="alert"><div>{{ session('echto.error') }}</div></div>
    @endif
    @yield('content')
</main>
</body>
</html>
