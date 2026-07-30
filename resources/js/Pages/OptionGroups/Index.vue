<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import CurrencyInput from '@/components/CurrencyInput.vue';

const props = defineProps({ groups: Array });

// ---------- Criar novo grupo ----------
const newGroup = useForm({
    name: '',
    items: [{ name: '', price_cents: 0 }],
});

function addNewItem() {
    newGroup.items.push({ name: '', price_cents: 0 });
}
function removeNewItem(i) {
    newGroup.items.splice(i, 1);
}

function createGroup() {
    newGroup.post(route('tenant.option-groups.store'), {
        preserveScroll: true,
        onSuccess: () => {
            newGroup.reset();
            newGroup.items = [{ name: '', price_cents: 0 }];
        },
    });
}

// ---------- Editar grupo existente ----------
const editingId = ref(null);
const editForms = reactive({});

function startEdit(group) {
    editingId.value = group.id;
    editForms[group.id] = useForm({
        name: group.name,
        is_active: group.is_active,
        items: group.items.map((i) => ({ id: i.id, name: i.name, price_cents: i.price_cents })),
    });
}

function cancelEdit() {
    editingId.value = null;
}

function saveEdit(group) {
    editForms[group.id].put(route('tenant.option-groups.update', group.id), {
        preserveScroll: true,
        onSuccess: () => (editingId.value = null),
    });
}

function addEditItem(group) {
    editForms[group.id].items.push({ id: null, name: '', price_cents: 0 });
}
function removeEditItem(group, i) {
    editForms[group.id].items.splice(i, 1);
}

function destroyGroup(group) {
    if (confirm(`Excluir o grupo "${group.name}"? Ele será desvinculado de todos os produtos.`)) {
        router.delete(route('tenant.option-groups.destroy', group.id), { preserveScroll: true });
    }
}
</script>

<template>
    <h1 class="text-xl font-medium text-zinc-900 mb-1">Grupos de adicionais</h1>
    <p class="text-sm text-zinc-500 mb-6">
        Crie grupos reutilizáveis (ex: "Bordas recheadas", "Molhos") — depois vincule cada um a
        um ou mais produtos, definindo se é obrigatório e quantas opções o cliente pode escolher.
    </p>

    <!-- Criar novo grupo -->
    <div class="bg-white rounded-xl border border-zinc-200 p-5 mb-6 max-w-2xl">
        <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-3">Novo grupo</p>
        <input
            v-model="newGroup.name"
            placeholder="Nome do grupo (ex: Bordas recheadas)"
            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm mb-3"
        />
        <p v-if="newGroup.errors.name" class="text-xs text-red-500 -mt-2 mb-3">{{ newGroup.errors.name }}</p>

        <div class="space-y-2 mb-3">
            <div v-for="(item, i) in newGroup.items" :key="i" class="flex gap-2 items-center">
                <input v-model="item.name" placeholder="Nome do item (ex: Catupiry)" class="flex-1 px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                <CurrencyInput v-model="item.price_cents" placeholder="R$" class="w-24 px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                <button type="button" @click="removeNewItem(i)" class="text-red-500 text-xs px-1">✕</button>
            </div>
        </div>
        <button type="button" @click="addNewItem" class="text-xs text-primary mb-4">+ Adicionar item</button>

        <div>
            <button
                type="button"
                @click="createGroup"
                :disabled="newGroup.processing"
                class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium disabled:opacity-60"
            >
                Criar grupo
            </button>
        </div>
    </div>

    <!-- Lista de grupos -->
    <div class="space-y-3 max-w-2xl">
        <div v-for="group in groups" :key="group.id" class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
            <!-- Visualização -->
            <div v-if="editingId !== group.id" class="flex items-center justify-between px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-zinc-900">{{ group.name }}</p>
                    <p class="text-xs text-zinc-500">{{ group.items.length }} item(ns)</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2 py-1 rounded-full" :class="group.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500'">
                        {{ group.is_active ? 'Ativo' : 'Inativo' }}
                    </span>
                    <button @click="startEdit(group)" class="text-xs text-primary">Editar</button>
                    <button @click="destroyGroup(group)" class="text-xs text-red-500">Excluir</button>
                </div>
            </div>

            <!-- Edição inline -->
            <div v-else class="p-4 bg-zinc-50 space-y-3">
                <input v-model="editForms[group.id].name" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" />

                <div class="space-y-2">
                    <div v-for="(item, i) in editForms[group.id].items" :key="i" class="flex gap-2 items-center">
                        <input v-model="item.name" class="flex-1 px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white" />
                        <CurrencyInput v-model="item.price_cents" class="w-24 px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white" />
                        <button type="button" @click="removeEditItem(group, i)" class="text-red-500 text-xs px-1">✕</button>
                    </div>
                </div>
                <button type="button" @click="addEditItem(group)" class="text-xs text-primary">+ Adicionar item</button>

                <label class="flex items-center gap-2 text-sm text-zinc-700 pt-2 border-t border-zinc-200">
                    <input type="checkbox" v-model="editForms[group.id].is_active" /> Grupo ativo
                </label>

                <div class="flex items-center gap-3 pt-1">
                    <button
                        @click="saveEdit(group)"
                        :disabled="editForms[group.id].processing"
                        class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium disabled:opacity-60"
                    >
                        Salvar
                    </button>
                    <button @click="cancelEdit" class="text-sm text-zinc-500">Cancelar</button>
                </div>
            </div>
        </div>

        <p v-if="!groups.length" class="text-center text-sm text-zinc-400 py-8">
            Nenhum grupo de adicionais ainda — crie o primeiro acima.
        </p>
    </div>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default { layout: AppLayout };
</script>