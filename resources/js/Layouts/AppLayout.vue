<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const store = computed(() => page.props.store);
const tenant = computed(() => page.props.tenant);

// 4 seções principais — cabe inteiro na bottom nav do mobile sem precisar
// de menu hambúrguer (decisão da Fase 9: 1 toque é mais rápido que drawer).
const navItems = [
    { label: 'Dashboard', href: route('tenant.dashboard') },
    { label: 'Cardápio', href: '#' },
    { label: 'Adicionais', href: '#' },
    { label: 'Configurações', href: '#' },
];

const toggling = ref(false);

function toggleStore() {
    toggling.value = true;
    router.put(route('tenant.store.toggle'), {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => (toggling.value = false),
    });
}
</script>

<template>
    <div class="min-h-screen bg-zinc-50">
        <!-- Sidebar desktop -->
        <aside class="hidden md:flex md:w-56 md:flex-col md:fixed md:inset-y-0 border-r border-zinc-200 bg-white">
            <div class="flex items-center gap-2 px-4 h-16 border-b border-zinc-200">
                <div class="w-7 h-7 rounded-md bg-primary flex items-center justify-center text-white text-xs font-semibold">MZ</div>
                <span class="font-medium text-zinc-900">MenuZap</span>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-zinc-600 hover:bg-zinc-100"
                    :class="{ 'bg-primary/10 text-primary font-medium': page.url === item.href }"
                >
                    {{ item.label }}
                </Link>
            </nav>
        </aside>

        <div class="md:pl-56">
            <!-- Topbar -->
            <header class="h-16 border-b border-zinc-200 bg-white flex items-center justify-between px-4 md:px-6 sticky top-0 z-10">
                <span class="font-medium text-zinc-900 truncate">{{ store?.name }}</span>
                <div class="flex items-center gap-3">
                    <a
                        v-if="tenant"
                        :href="`/${tenant.slug}`"
                        target="_blank"
                        class="hidden sm:inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-zinc-300 text-zinc-600 hover:bg-zinc-50"
                    >
                        Ver minha loja
                    </a>
                    <button
                        @click="toggleStore"
                        :disabled="toggling"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium transition disabled:opacity-60"
                        :class="store?.is_open
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            : 'bg-zinc-100 text-zinc-500 border border-zinc-200'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" :class="store?.is_open ? 'bg-emerald-500' : 'bg-zinc-400'"></span>
                        {{ store?.is_open ? 'Loja aberta' : 'Loja fechada' }}
                    </button>
                </div>
            </header>

            <main class="p-4 md:p-6 pb-20 md:pb-6">
                <slot />
            </main>
        </div>

        <!-- Bottom nav mobile — substitui o menu hambúrguer (Fase 9) -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 h-14 bg-white border-t border-zinc-200 flex items-center justify-around z-10">
            <Link
                v-for="item in navItems"
                :key="item.label"
                :href="item.href"
                class="flex flex-col items-center gap-0.5 text-[11px] text-zinc-500"
                :class="{ 'text-primary font-medium': page.url === item.href }"
            >
                {{ item.label }}
            </Link>
        </nav>
    </div>
</template>