<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $store->name }} — Cardápio</title>

    {{-- Catálogo completo embutido como JSON — o modal de personalização
         lê daqui, sem nenhuma requisição extra ao servidor. Continua
         compatível com o cache Redis da Parte 3: o JSON fica estático
         dentro do HTML cacheado, junto com o resto da página. --}}
    <script>window.__CATALOG__ = @js($categories);</script>

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

<div x-data="storefront('{{ $categories->first()?->id }}')">

    <!-- Hero -->
    <header class="relative h-48 overflow-hidden bg-gradient-to-br from-primary to-primary/70">
        @if($store->banner_url)
            <img src="{{ $store->banner_url }}" class="absolute inset-0 w-full h-full object-cover" alt="">
        @endif
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

                <div class="flex items-center gap-2">
                    @if($store->instagram_url)
                        @php
                            // Aceita tanto "@handle" quanto uma URL completa —
                            // o lojista não precisa saber montar o link certo.
                            $instagramHandle = ltrim($store->instagram_url, '@');
                            $instagramUrl = str_starts_with($instagramHandle, 'http')
                                ? $instagramHandle
                                : "https://instagram.com/{$instagramHandle}";
                        @endphp
                        <a href="{{ $instagramUrl }}" target="_blank"
                           class="w-9 h-9 rounded-full bg-white/95 flex items-center justify-center text-pink-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85C2.35 3.86 3.87 2.33 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16zM12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67.01 15.26 0 12 0zm0 5.84A6.16 6.16 0 1018.16 12 6.16 6.16 0 0012 5.84zM12 16a4 4 0 110-8 4 4 0 010 8zm6.41-10.85a1.44 1.44 0 100 2.88 1.44 1.44 0 000-2.88z"/></svg>
                        </a>
                    @endif

                    @if($store->whatsapp_contact)
                        <a href="https://wa.me/{{ $store->whatsapp_contact }}" target="_blank"
                           class="w-9 h-9 rounded-full bg-white/95 flex items-center justify-center text-emerald-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.35 5.07L2 22l5.06-1.33A9.94 9.94 0 0012 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.62 0-3.14-.44-4.44-1.2l-.32-.19-3.3.87.88-3.22-.21-.33A7.94 7.94 0 014 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/></svg>
                        </a>
                    @endif
                </div>
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
                                        @click="openModal('{{ $product->id }}')"
                                        class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center text-base leading-none"
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

    <!-- Modal de personalização (bottom sheet) -->
    <div x-show="open" x-cloak class="fixed inset-0 z-30 flex items-end sm:items-center sm:justify-center">
        <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
            x-transition:enter-end="translate-y-0 sm:opacity-100"
            class="relative bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl max-h-[85vh] flex flex-col"
        >
            <template x-if="product">
                <div class="flex flex-col overflow-hidden">
                    <!-- Cabeçalho -->
                    <div class="relative h-40 bg-gradient-to-br from-primary/20 to-primary/40 flex-shrink-0">
                        <img x-show="product.image_url" :src="product.image_url" class="w-full h-full object-cover">
                        <button @click="closeModal()" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/40 text-white flex items-center justify-center">✕</button>
                    </div>

                    <div class="p-4 overflow-y-auto flex-1">
                        <div class="flex items-start justify-between gap-3 mb-1">
                            <h3 class="text-base font-semibold text-zinc-900" x-text="product.name"></h3>
                            <span class="text-base font-semibold text-primary whitespace-nowrap" x-text="formatPrice(unitPriceCents)"></span>
                        </div>
                        <p class="text-sm text-zinc-500 mb-4" x-text="product.description"></p>

                        <!-- Tamanhos -->
                        <template x-if="product.has_sizes">
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-zinc-900">Escolha o tamanho</span>
                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Obrigatório</span>
                                </div>
                                <div class="space-y-2">
                                    <template x-for="size in product.sizes" :key="size.id">
                                        <label
                                            class="flex items-center justify-between border rounded-lg px-3 py-2 cursor-pointer"
                                            :class="selectedSizeId === size.id ? 'border-primary bg-primary/5' : 'border-zinc-200'"
                                        >
                                            <span class="flex items-center gap-2 text-sm text-zinc-900">
                                                <input type="radio" :checked="selectedSizeId === size.id" @change="selectedSizeId = size.id" class="accent-current" style="color: rgb(var(--c-primary))">
                                                <span x-text="size.name"></span>
                                            </span>
                                            <span class="text-sm text-zinc-600" x-text="formatPrice(size.price_cents)"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Grupos de adicionais -->
                        <template x-for="group in product.option_groups" :key="group.id">
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-zinc-900" x-text="group.name"></span>
                                    <span
                                        class="text-[11px] px-2 py-0.5 rounded-full transition-colors"
                                        :class="isGroupValid(group)
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : (group.pivot.min_selections > 0 ? 'bg-amber-50 text-amber-700' : 'bg-zinc-100 text-zinc-500')"
                                        x-text="groupStatusLabel(group)"
                                    ></span>
                                </div>
                                <div class="space-y-2">
                                    <template x-for="item in group.items" :key="item.id">
                                        <label
                                            class="flex items-center justify-between border rounded-lg px-3 py-2 cursor-pointer"
                                            :class="isItemSelected(group, item) ? 'border-primary bg-primary/5' : 'border-zinc-200'"
                                        >
                                            <span class="flex items-center gap-2 text-sm text-zinc-900">
                                                <input
                                                    :type="group.pivot.max_selections === 1 ? 'radio' : 'checkbox'"
                                                    :checked="isItemSelected(group, item)"
                                                    @change="toggleItem(group, item)"
                                                >
                                                <span x-text="item.name"></span>
                                            </span>
                                            <span class="text-xs text-zinc-500" x-text="item.price_cents > 0 ? '+ ' + formatPrice(item.price_cents) : 'Grátis'"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Observações -->
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-zinc-900 mb-2">Observações</label>
                            <textarea
                                x-model="notes"
                                rows="2"
                                placeholder="Ex: sem cebola, ponto da carne..."
                                class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Rodapé fixo -->
                    <div class="border-t border-zinc-100 p-4 flex-shrink-0">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-zinc-600">Quantidade</span>
                            <div class="flex items-center gap-3">
                                <button @click="quantity = Math.max(1, quantity - 1)" class="w-7 h-7 rounded-full border border-zinc-300 flex items-center justify-center text-sm">−</button>
                                <span class="text-sm font-medium w-4 text-center" x-text="quantity"></span>
                                <button @click="quantity++" class="w-7 h-7 rounded-full border border-zinc-300 flex items-center justify-center text-sm">+</button>
                            </div>
                        </div>
                        <button
                            @click="addToCart()"
                            :disabled="!allGroupsValid"
                            class="w-full rounded-xl px-4 py-3 flex items-center justify-between text-sm font-medium transition-colors"
                            :class="allGroupsValid ? 'bg-primary text-white' : 'bg-zinc-200 text-zinc-400 cursor-not-allowed'"
                        >
                            <span x-text="allGroupsValid ? 'Adicionar ao carrinho' : 'Preencha os campos obrigatórios'"></span>
                            <span x-show="allGroupsValid" x-text="formatPrice(totalCents)"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
</body>
</html>