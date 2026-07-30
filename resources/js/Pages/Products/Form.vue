<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    categories: Array,
    product: Object,
});

const isEdit = !!props.product;

const form = useForm({
    category_id: props.product?.category_id ?? (props.categories[0]?.id ?? ''),
    name: props.product?.name ?? '',
    description: props.product?.description ?? '',
    has_sizes: props.product?.has_sizes ?? false,
    base_price_cents: props.product?.base_price_cents ?? 0,
    sizes: props.product?.sizes?.map((s) => ({ name: s.name, price_cents: s.price_cents })) ?? [],
    badge: props.product?.badge ?? '',
    is_active: props.product?.is_active ?? true,
    image: null,
});

const basePriceReais = computed({
    get: () => (form.base_price_cents / 100).toFixed(2),
    set: (v) => (form.base_price_cents = Math.round(parseFloat(v || 0) * 100)),
});

function addSize() {
    form.sizes.push({ name: '', price_cents: 0 });
}
function removeSize(i) {
    form.sizes.splice(i, 1);
}
function sizePriceReais(i) {
    return (form.sizes[i].price_cents / 100).toFixed(2);
}
function setSizePriceReais(i, v) {
    form.sizes[i].price_cents = Math.round(parseFloat(v || 0) * 100);
}

const imagePreview = ref(props.product?.image_url ?? null);
function onImageChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    imagePreview.value = URL.createObjectURL(file);
}
function removeImage() {
    form.image = null;
    imagePreview.value = null;
}

function toggleBadge(value) {
    form.badge = form.badge === value ? '' : value;
}

function submit() {
    const url = isEdit
        ? route('tenant.products.update', props.product.id)
        : route('tenant.products.store');

    form.transform((data) => ({ ...data, _method: isEdit ? 'put' : 'post' }))
        .post(url, { forceFormData: true });
}
</script>

<template>
    <h1 class="text-xl font-medium text-zinc-900 mb-1">{{ isEdit ? 'Editar produto' : 'Novo produto' }}</h1>
    <p class="text-sm text-zinc-500 mb-6">Preencha os dados essenciais e a mídia do produto.</p>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-5 gap-6 max-w-4xl">
        <!-- Coluna esquerda: essencial -->
        <div class="lg:col-span-3 space-y-4">
            <div class="bg-white rounded-xl border border-zinc-200 p-5 space-y-4">
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Nome do produto</label>
                    <input v-model="form.name" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                    <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Categoria</label>
                    <select v-model="form.category_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                    <a :href="route('tenant.categories.index')" target="_blank" class="text-xs text-primary mt-1 inline-block">
                        + Nova categoria (abre em nova aba)
                    </a>
                </div>

                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Descrição</label>
                    <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm"></textarea>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-zinc-100">
                    <div>
                        <p class="text-sm text-zinc-900">Este produto tem tamanhos diferentes?</p>
                        <p class="text-xs text-zinc-500">Preço base é substituído pelas variações</p>
                    </div>
                    <input type="checkbox" v-model="form.has_sizes" class="w-4 h-4 flex-shrink-0" />
                </div>

                <div v-if="!form.has_sizes">
                    <label class="block text-xs text-zinc-600 mb-1">Preço (R$)</label>
                    <input v-model="basePriceReais" type="number" step="0.01" min="0" class="w-full max-w-[160px] px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                </div>

                <div v-else class="bg-zinc-50 rounded-lg p-3 space-y-2">
                    <div v-for="(size, i) in form.sizes" :key="i" class="flex gap-2 items-center">
                        <input v-model="size.name" placeholder="Nome (ex: Grande)" class="flex-1 px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                        <input
                            :value="sizePriceReais(i)"
                            @input="setSizePriceReais(i, $event.target.value)"
                            type="number" step="0.01" min="0" placeholder="Preço"
                            class="w-28 px-3 py-2 rounded-lg border border-zinc-300 text-sm"
                        />
                        <button type="button" @click="removeSize(i)" class="text-red-500 text-xs px-1">✕</button>
                    </div>
                    <button type="button" @click="addSize" class="text-xs text-primary flex items-center gap-1">+ Adicionar tamanho</button>
                </div>
            </div>
        </div>

        <!-- Coluna direita: mídia e visibilidade -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl border border-zinc-200 p-5 space-y-3">
                <label class="block text-xs text-zinc-600">Foto do produto</label>

                <div v-if="imagePreview" class="relative">
                    <img :src="imagePreview" class="w-full aspect-square object-cover rounded-lg border border-zinc-200" />
                    <div class="absolute top-2 right-2 flex gap-1">
                        <label class="bg-white/90 text-xs px-2 py-1 rounded-md cursor-pointer border border-zinc-200">
                            Trocar
                            <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                        </label>
                        <button type="button" @click="removeImage" class="bg-white/90 text-xs px-2 py-1 rounded-md border border-zinc-200 text-red-500">
                            Remover
                        </button>
                    </div>
                </div>
                <label v-else class="flex flex-col items-center justify-center gap-1 border-2 border-dashed border-zinc-300 rounded-lg aspect-square cursor-pointer text-zinc-400 text-xs">
                    Toque para adicionar foto
                    <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                </label>
            </div>

            <div class="bg-white rounded-xl border border-zinc-200 p-5 space-y-4">
                <div>
                    <label class="block text-xs text-zinc-600 mb-2">Badge de destaque</label>
                    <div class="flex gap-2 flex-wrap">
                        <button type="button" @click="toggleBadge('promo')" class="text-xs px-3 py-1.5 rounded-full border"
                            :class="form.badge === 'promo' ? 'bg-amber-50 border-amber-300 text-amber-700' : 'border-zinc-300 text-zinc-600'">
                            Promoção
                        </button>
                        <button type="button" @click="toggleBadge('new')" class="text-xs px-3 py-1.5 rounded-full border"
                            :class="form.badge === 'new' ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'border-zinc-300 text-zinc-600'">
                            Novo
                        </button>
                        <button type="button" @click="toggleBadge('highlight')" class="text-xs px-3 py-1.5 rounded-full border"
                            :class="form.badge === 'highlight' ? 'bg-primary/10 border-primary/30 text-primary' : 'border-zinc-300 text-zinc-600'">
                            Destaque
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-zinc-100">
                    <span class="text-sm text-zinc-900">Produto ativo</span>
                    <input type="checkbox" v-model="form.is_active" class="w-4 h-4" />
                </div>

                <p class="text-xs text-zinc-400 pt-3 border-t border-zinc-100">
                    Grupos de adicionais chegam na próxima parte do painel.
                </p>
            </div>
        </div>

        <div class="lg:col-span-5 flex items-center gap-3">
            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-lg bg-primary text-white text-sm font-medium disabled:opacity-60">
                {{ form.processing ? 'Salvando...' : 'Salvar produto' }}
            </button>
            <a :href="route('tenant.products.index')" class="text-sm text-zinc-500">Cancelar</a>
        </div>
    </form>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default { layout: AppLayout };
</script>