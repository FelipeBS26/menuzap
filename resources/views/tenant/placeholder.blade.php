<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Dashboard — MenuZap</title></head>
<body style="font-family: system-ui, sans-serif; padding: 2rem;">
    <h1>Bem-vindo, {{ $user->name }}</h1>
    <p>Loja: <strong>{{ $tenant->slug }}</strong> · Plano: {{ $tenant->plan->name }} · Status: {{ $tenant->status }}</p>
    <p>Painel completo (sidebar, métricas, CRUD) chega no Sprint 2.</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Sair</button>
    </form>
</body>
</html>