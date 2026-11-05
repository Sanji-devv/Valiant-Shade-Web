<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Valiant Shade') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #0f172a; }
        main { text-align: center; padding: 2rem; }
        h1 { margin-bottom: .5rem; }
        p { color: #64748b; margin-bottom: 1.5rem; }
        nav { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        a { color: #2563eb; text-decoration: none; padding: .5rem 1rem; border: 1px solid #cbd5e1; border-radius: .5rem; background: #fff; }
        a:hover { border-color: #2563eb; }
    </style>
</head>
<body>
    <main>
        <h1>Valiant Shade</h1>
        <p>Front-facing member and visitor portal — ready for adventure.</p>
        <nav>
            <a href="{{ route('web.feldan') }}">Feldan Krallığı</a>
            <a href="{{ route('web.rovesta') }}">Rovesta Krallığı</a>
            <a href="{{ route('web.valiant-shade') }}">Lonca Arazisi</a>
            <a href="{{ route('web.newspaper') }}">Gazete</a>
            <a href="{{ url('/admin') }}">Control Panel</a>
            <a href="{{ url('/demo-vuexy') }}">Vuexy Demo Reference</a>
        </nav>
    </main>
</body>
</html>
