<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Valiant Shade') }} — Geliştirici</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0d0b09; color: #e8e0d4; }
        main { text-align: center; padding: 2rem; max-width: 36rem; }
        h1 { margin-bottom: .5rem; color: #c9a84c; }
        p { color: #9a9088; margin-bottom: 1.5rem; }
        nav { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        a { color: #c9a84c; text-decoration: none; padding: .5rem 1rem; border: 1px solid rgba(201,168,76,.35); border-radius: .5rem; }
        a:hover { background: rgba(201,168,76,.1); }
    </style>
</head>
<body>
    <main>
        <h1>Valiant Shade</h1>
        <p>Geliştirici ve yönetici bağlantıları</p>
        <nav>
            <a href="{{ route('web.feldan') }}">Oyuncu girişi (Feldan)</a>
            <a href="{{ route('web.valiant-shade.edit') }}">Lonca editörü</a>
            <a href="{{ route('web.isonburg.city.edit') }}">Isonburg şehir editörü</a>
            <a href="{{ route('web.isonburg.terrain.edit') }}">Isonburg arazi editörü</a>
            <a href="{{ route('web.login') }}">Editör girişi</a>
            <a href="{{ url('/admin') }}">Yönetim paneli</a>
        </nav>
    </main>
</body>
</html>
