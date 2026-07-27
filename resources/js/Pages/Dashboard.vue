<script setup>
defineProps({
    store: Object,
    planName: String,
    metrics: Object,
});
</script>

<template>
    <h1 class="text-xl font-medium text-zinc-900 mb-1">Visão geral</h1>
    <p class="text-sm text-zinc-500 mb-6">{{ store?.name }} · Plano {{ planName }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <p class="text-xs text-zinc-500 mb-1">Pedidos hoje</p>
            <p class="text-2xl font-medium text-zinc-900">{{ metrics.ordersToday }}</p>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <p class="text-xs text-zinc-500 mb-1">Produtos ativos</p>
            <p class="text-2xl font-medium text-zinc-900">{{ metrics.activeProducts }}</p>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <p class="text-xs text-zinc-500 mb-1">Categorias</p>
            <p class="text-2xl font-medium text-zinc-900">{{ metrics.categories }}</p>
        </div>
    </div>
</template>

<script>
// Layout persistente do Inertia: a Sidebar/Topbar/BottomNav do AppLayout
// NÃO remontam ao navegar entre páginas — decisão técnica da Fase 9 que só
// funciona registrando o layout assim, fora do <script setup>.
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>