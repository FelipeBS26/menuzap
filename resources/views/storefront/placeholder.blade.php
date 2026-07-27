<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $store->name ?? $tenant->slug }} — MenuZap</title>
</head>
<body style="font-family: system-ui, sans-serif; padding: 2rem;">
    <h1>{{ $store->name }}</h1>
    <p>Vitrine em construção — o hero, o cardápio e o carrinho chegam no Sprint 3.</p>
    <p>Status do tenant: <strong>{{ $tenant->status }}</strong> · WhatsApp: {{ $store->whatsapp_number }}</p>
</body>
</html>