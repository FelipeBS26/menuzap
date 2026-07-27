<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Entrar — MenuZap</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f4f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 2rem; border-radius: 12px; width: 100%; max-width: 360px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { font-size: 1.25rem; margin: 0 0 1.5rem; }
        label { display: block; font-size: .8rem; margin-bottom: .25rem; color: #52525b; }
        input { width: 100%; padding: .5rem .75rem; margin-bottom: 1rem; border: 1px solid #d4d4d8; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: .6rem; background: #15803d; color: #fff; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; }
        .error { color: #dc2626; font-size: .8rem; margin: -.5rem 0 1rem; }
        .link { text-align: center; margin-top: 1rem; font-size: .85rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Entrar no MenuZap</h1>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Senha</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Entrar</button>
        </form>
        <p class="link">Não tem conta? <a href="{{ route('register') }}">Criar loja</a></p>
    </div>
</body>
</html>