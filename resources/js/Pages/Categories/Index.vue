<script setup>
import { useForm, router } from '@inertiajs/vue3';

defineProps({ categories: Array });

const form = useForm({ name: '' });

function submit() {
    form.post(route('tenant.categories.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function toggleActive(category) {
    router.put(route('tenant.categories.update', category.id), {
        name: category.name,
        is_active: !category.is_active,
    }, { preserveScroll: true });
}

function remove(category) {
    if (confirm(`Excluir a categoria "${category.name}"? Se houver produtos vinculados, a exclusão será bloqueada.`)) {
        router.delete(route('tenant.categories.destroy', category.id), { preserveScroll: true });
    }
}

function move(category, direction) {
    router.put(route('tenant.categories.move', category.id), { direction }, { preserveScroll: true });
}
</script>

<template>
    <div class="flex items-center justify-between mb-1">
        <h1 class="text-xl font-medium text-zinc-900">Categorias</h1>
        <a :href="route('tenant.products.index')" class="text-sm text-primary font-medium">Ver produtos →</a>
    </div>
    <p class="text-sm text-zinc-500 mb-6">Organize o cardápio em grupos — Hambúrgueres, Bebidas, Sobremesas...</p>

    <form @submit.prevent="submit" class="flex gap-2 mb-6 max-w-md">
        <input
            v-model="form.name"
            placeholder="Nome da nova categoria"
            class="flex-1 px-3 py-2 rounded-lg border border-zinc-300 text-sm"
        />
        <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium disabled:opacity-60"
        >
            Adicionar
        </button>
    </form>
    <p v-if="form.errors.name" class="text-xs text-red-500 -mt-4 mb-4">{{ form.errors.name }}</p>

    <div class="bg-white rounded-xl border border-zinc-200 divide-y divide-zinc-100 max-w-2xl">
        <div v-for="category in categories" :key="category.id" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="flex flex-col leading-none">
                    <button @click="move(category, 'up')" class="text-zinc-400 hover:text-zinc-700 text-[10px]">▲</button>
                    <button @click="move(category, 'down')" class="text-zinc-400 hover:text-zinc-700 text-[10px]">▼</button>
                </div>
                <span class="text-sm text-zinc-900" :class="{ 'text-zinc-400 line-through': !category.is_active }">
                    {{ category.name }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                <button
                    @click="toggleActive(category)"
                    class="text-xs px-2.5 py-1 rounded-full"
                    :class="category.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500'"
                >
                    {{ category.is_active ? 'Ativa' : 'Inativa' }}
                </button>
                <button @click="remove(category)" class="text-xs text-red-500 hover:text-red-700">Excluir</button>
            </div>
        </div>
        <p v-if="!categories.length" class="px-4 py-8 text-sm text-zinc-400 text-center">
            Nenhuma categoria ainda — adicione a primeira acima.
        </p>
    </div>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default { layout: AppLayout };
</script>