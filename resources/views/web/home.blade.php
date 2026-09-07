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
        <p>Ön yüz (üye / ziyaretçi) alanı — geliştirme için hazır.</p>
        <nav>
            <a href="{{ route('web.valiant-shade') }}">Valiant Shade Arazisi</a>
            <a href="{{ route('web.valiant-shade.edit') }}">Harita Editörü (Yönetici)</a>
            <a href="{{ route('web.login') }}">Editör Girişi</a>
            <a href="{{ url('/admin') }}">Yönetim Paneli</a>
        </nav>
    </main>
</body>
</html>
