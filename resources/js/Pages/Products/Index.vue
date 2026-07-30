<script setup>
import { router } from '@inertiajs/vue3';

defineProps({ products: Array });

function destroy(product) {
    if (confirm(`Excluir "${product.name}"?`)) {
        router.delete(route('tenant.products.destroy', product.id), { preserveScroll: true });
    }
}

function duplicate(product) {
    router.post(route('tenant.products.duplicate', product.id), {}, { preserveScroll: true });
}

function priceLabel(product) {
    if (product.has_sizes) {
        const prices = (product.sizes ?? []).map((s) => s.price_cents);
        if (!prices.length) return '—';
        return `A partir de R$ ${(Math.min(...prices) / 100).toFixed(2)}`;
    }
    return `R$ ${(product.base_price_cents / 100).toFixed(2)}`;
}
</script>

<template>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-medium text-zinc-900 mb-1">Produtos</h1>
            <p class="text-sm text-zinc-500">{{ products.length }} produto(s) cadastrado(s)</p>
        </div>
        <a :href="route('tenant.products.create')" class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">
            + Novo produto
        </a>
    </div>

    <!-- Desktop: tabela densa -->
    <div class="hidden md:block bg-white rounded-xl border border-zinc-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-100 text-xs text-zinc-500 uppercase tracking-wide">
                    <th class="text-left px-4 py-3 font-medium">Produto</th>
                    <th class="text-left px-4 py-3 font-medium">Categoria</th>
                    <th class="text-left px-4 py-3 font-medium">Preço</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="product in products" :key="product.id" class="border-b border-zinc-100 last:border-none">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img v-if="product.thumbnail_url" :src="product.thumbnail_url" class="w-9 h-9 rounded-md object-cover" />
                            <div v-else class="w-9 h-9 rounded-md bg-zinc-100"></div>
                            <span class="font-medium text-zinc-900">{{ product.name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-zinc-600">{{ product.category?.name }}</td>
                    <td class="px-4 py-3 text-zinc-600">{{ priceLabel(product) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-1 rounded-full" :class="product.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500'">
                            {{ product.is_active ? 'Ativo' : 'Indisponível' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a :href="route('tenant.products.edit', product.id)" class="text-xs text-primary mr-3">Editar</a>
                        <button @click="duplicate(product)" class="text-xs text-zinc-500 mr-3">Duplicar</button>
                        <button @click="destroy(product)" class="text-xs text-red-500">Excluir</button>
                    </td>
                </tr>
                <tr v-if="!products.length">
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-400">Nenhum produto cadastrado ainda.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Mobile: cards empilhados (Fase 9 — nunca tabela com scroll horizontal) -->
    <div class="md:hidden space-y-2">
        <div v-for="product in products" :key="product.id" class="bg-white rounded-xl border border-zinc-200 p-3 flex items-center gap-3">
            <img v-if="product.thumbnail_url" :src="product.thumbnail_url" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
            <div v-else class="w-12 h-12 rounded-lg bg-zinc-100 flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-zinc-900 truncate">{{ product.name }}</p>
                <p class="text-xs text-zinc-500">{{ product.category?.name }} · {{ priceLabel(product) }}</p>
            </div>
            <a :href="route('tenant.products.edit', product.id)" class="text-xs text-primary flex-shrink-0">Editar</a>
        </div>
        <p v-if="!products.length" class="text-center text-sm text-zinc-400 py-8">Nenhum produto cadastrado ainda.</p>
    </div>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default { layout: AppLayout };
</script>