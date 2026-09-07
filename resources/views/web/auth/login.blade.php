<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Harita Editörü Girişi — {{ config('app.name', 'Valiant Shade') }}</title>
    <style>
        :root { --bg: #1a1410; --gold: #c9a84c; --text: #f5efe3; --muted: #a89878; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: var(--bg); color: var(--text); font-family: system-ui, sans-serif; }
        .card { width: min(24rem, calc(100% - 2rem)); padding: 2rem; border: 1px solid rgba(201,168,76,.35); border-radius: .75rem; background: rgba(12,10,8,.95); }
        h1 { margin: 0 0 .25rem; font-size: 1.25rem; }
        p { margin: 0 0 1.5rem; color: var(--muted); font-size: .875rem; }
        label { display: block; margin-bottom: .35rem; font-size: .8125rem; color: var(--muted); }
        input[type=email], input[type=password] { width: 100%; padding: .6rem .75rem; margin-bottom: 1rem; border: 1px solid rgba(201,168,76,.35); border-radius: .375rem; background: rgba(26,20,16,.9); color: var(--text); font: inherit; }
        button { width: 100%; padding: .65rem; border: 1px solid var(--gold); border-radius: .375rem; background: rgba(201,168,76,.15); color: var(--text); font: inherit; cursor: pointer; }
        button:hover { background: rgba(201,168,76,.25); }
        .error { color: #f0a0a0; font-size: .8125rem; margin-bottom: 1rem; }
        .back { display: block; margin-top: 1rem; text-align: center; color: var(--muted); font-size: .8125rem; }
        .remember { display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; font-size: .8125rem; color: var(--muted); }
    </style>
</head>
<body>
    <form class="card" method="POST" action="{{ route('web.login.submit') }}">
        @csrf
        <input type="hidden" name="redirect" value="{{ $redirect }}">
        <h1>Harita Editörü</h1>
        <p>Yalnızca yöneticiler haritayı düzenleyebilir.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <label for="email">E-posta</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Şifre</label>
        <input id="password" type="password" name="password" required>

        <label class="remember">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            Beni hatırla
        </label>

        <button type="submit">Giriş Yap</button>
        <a class="back" href="{{ route('web.valiant-shade') }}">← Haritaya dön</a>
    </form>
</body>
</html>
