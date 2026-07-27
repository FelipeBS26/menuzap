<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Criar loja — MenuZap</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f4f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
        .card { background: #fff; padding: 2rem; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
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
        <h1>Criar sua loja no MenuZap</h1>
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <label for="store_name">Nome da loja</label>
            <input type="text" name="store_name" id="store_name" value="{{ old('store_name') }}" required>
            @error('store_name') <div class="error">{{ $message }}</div> @enderror

            <label for="name">Seu nome</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror

            <label for="whatsapp_number">WhatsApp para receber pedidos</label>
            <input type="text" name="whatsapp_number" id="whatsapp_number" placeholder="5551999990000" value="{{ old('whatsapp_number') }}" required>
            @error('whatsapp_number') <div class="error">{{ $message }}</div> @enderror

            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Senha</label>
            <input type="password" name="password" id="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <label for="password_confirmation">Confirmar senha</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required>

            <button type="submit">Criar minha loja</button>
        </form>
        <p class="link">Já tem conta? <a href="{{ route('login') }}">Entrar</a></p>
    </div>
</body>
</html>