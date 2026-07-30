<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $store->name }} — Cardápio</title>
    @vite(['resources/css/app.css', 'resources/js/storefront.js'])

    <style>
        /* Obrigatório para x-cloak funcionar — Alpine não injeta essa regra
           sozinho. Sem isso, o botão do carrinho pisca visível por uma
           fração de segundo antes do Alpine inicializar. */
        [x-cloak] { display: none !important; }

        /* Cor dinâmica por tenant — RGB espaçado (Fase 8), já vem assim do
           banco (default "21 128 61"). Diferente da --c-primary fixa do
           painel: aqui é sempre a identidade visual da LOJA, não da marca
           MenuZap. Suporta opacidade nativa do Tailwind v4 (bg-primary/50). */
        :root {
            --c-primary: {{ $store->primary_color }};
        }
    </style>
</head>
<body class="antialiased bg-white text-zinc-900">

<div x-data="{ activeCategory: '{{ $categories->first()?->id }}' }">

    <!-- Hero -->
    <header class="relative h-48 bg-gradient-to-br from-primary to-primary/70">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

        <div class="relative h-full flex flex-col justify-between p-4">
            <div class="flex items-start justify-between">
                @if($store->is_open)
                    <span class="inline-flex items-center gap-1.5 bg-white/95 text-emerald-700 text-xs font-medium px-3 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Aberto agora
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 bg-white/95 text-zinc-500 text-xs font-medium px-3 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                        Fechado no momento
                    </span>
                @endif

                @if($store->whatsapp_contact)
                    <a href="https://wa.me/{{ $store->whatsapp_contact }}" target="_blank"
                       class="w-9 h-9 rounded-full bg-white/95 flex items-center justify-center text-emerald-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.35 5.07L2 22l5.06-1.33A9.94 9.94 0 0012 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.62 0-3.14-.44-4.44-1.2l-.32-.19-3.3.87.88-3.22-.21-.33A7.94 7.94 0 014 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/></svg>
                    </a>
                @endif
            </div>

            <div class="flex items-end gap-3">
                <div class="w-14 h-14 rounded-xl bg-white flex items-center justify-center text-primary font-semibold text-lg flex-shrink-0 overflow-hidden">
                    @if($store->logo_url)
                        <img src="{{ $store->logo_url }}" class="w-full h-full object-cover" alt="{{ $store->name }}">
                    @else
                        {{ mb_substr($store->name, 0, 2) }}
                    @endif
                </div>
                <div class="text-white pb-1">
                    <h1 class="text-lg font-semibold drop-shadow">{{ $store->name }}</h1>
                    @if($store->estimated_time_min)
                        <p class="text-xs opacity-90">{{ $store->estimated_time_min }} min · Entrega R$ {{ number_format($store->delivery_fee_cents / 100, 2, ',', '.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </header>

    @unless($store->is_open)
        <div class="bg-zinc-100 text-zinc-600 text-sm text-center py-2 px-4">
            {{ $store->closed_message ?: 'Estamos fechados no momento. Volte mais tarde!' }}
        </div>
    @endunless

    <!-- Navegação de categorias (sticky) -->
    <nav class="sticky top-0 z-20 bg-white border-b border-zinc-100 overflow-x-auto">
        <div class="flex gap-2 px-4 py-3 min-w-max">
            @foreach($categories as $category)
                <a href="#cat-{{ $category->id }}"
                   @click="activeCategory = '{{ $category->id }}'"
                   class="px-4 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition"
                   :class="activeCategory === '{{ $category->id }}' ? 'bg-primary text-white' : 'bg-zinc-100 text-zinc-600'">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </nav>

    <!-- Catálogo -->
    <main class="px-4 py-4 pb-28 max-w-2xl mx-auto">
        @forelse($categories as $category)
            <section id="cat-{{ $category->id }}" class="mb-8 scroll-mt-16">
                <h2 class="text-base font-semibold text-zinc-900 mb-3">{{ $category->name }}</h2>

                <div class="space-y-3">
                    @foreach($category->products as $product)
                        <div class="flex gap-3 border border-zinc-100 rounded-xl p-3">
                            <div class="w-20 h-20 rounded-lg bg-zinc-100 flex-shrink-0 overflow-hidden relative">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" loading="lazy" class="w-full h-full object-cover" alt="{{ $product->name }}">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-primary/10 to-primary/20"></div>
                                @endif

                                @if($product->badge === 'promo')
                                    <span class="absolute top-1 left-1 bg-amber-500 text-white text-[9px] font-medium px-1.5 py-0.5 rounded">Promoção</span>
                                @elseif($product->badge === 'new')
                                    <span class="absolute top-1 left-1 bg-emerald-500 text-white text-[9px] font-medium px-1.5 py-0.5 rounded">Novo</span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0 flex flex-col justify-between">
                                <div>
                                    <p class="text-sm font-medium text-zinc-900">{{ $product->name }}</p>
                                    @if($product->description)
                                        <p class="text-xs text-zinc-500 line-clamp-2 mt-0.5">{{ $product->description }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-sm font-medium text-zinc-900">
                                        @if($product->has_sizes && $product->sizes->isNotEmpty())
                                            A partir de R$ {{ number_format($product->sizes->min('price_cents') / 100, 2, ',', '.') }}
                                        @else
                                            R$ {{ number_format($product->base_price_cents / 100, 2, ',', '.') }}
                                        @endif
                                    </span>
                                    <button
                                        type="button"
                                        class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center text-base leading-none"
                                        {{-- @click abre o modal de personalização — chega na Parte 2 --}}
                                    >+</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-center text-sm text-zinc-400 py-16">Cardápio ainda em preparação.</p>
        @endforelse
    </main>

    <!-- Botão flutuante do carrinho -->
    <div
        x-show="$store.cart.count > 0"
        x-cloak
        class="fixed bottom-4 left-4 right-4 max-w-2xl mx-auto"
    >
        <button type="button" class="w-full bg-primary text-white rounded-xl px-4 py-3 flex items-center justify-between shadow-lg">
            <span class="flex items-center gap-2 text-sm font-medium">
                <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs" x-text="$store.cart.count"></span>
                Ver carrinho
            </span>
            <span class="text-sm font-medium" x-text="'R$ ' + ($store.cart.totalCents / 100).toFixed(2).replace('.', ',')"></span>
        </button>
    </div>

</div>
</body>
</html>