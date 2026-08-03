<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CurrencyInput from '@/components/CurrencyInput.vue';

const props = defineProps({ store: Object });

const form = useForm({
    name: props.store.name,
    description: props.store.description ?? '',
    primary_color: props.store.primary_color ?? '21 128 61',
    whatsapp_number: props.store.whatsapp_number,
    whatsapp_contact: props.store.whatsapp_contact ?? '',
    instagram_url: props.store.instagram_url ?? '',
    delivery_fee_cents: props.store.delivery_fee_cents,
    min_order_cents: props.store.min_order_cents,
    estimated_time_min: props.store.estimated_time_min ?? '',
    accepts_delivery: props.store.accepts_delivery,
    accepts_pickup: props.store.accepts_pickup,
    accepts_dine_in: props.store.accepts_dine_in,
    logo: null,
    banner: null,
});

// O banco guarda "R G B" espaçado (ex: "21 128 61" — Fase 8), mas o
// <input type="color"> nativo só entende hex (#15803D). Conversão só
// acontece na entrada/saída — o formato salvo nunca muda.
function rgbSpaceToHex(rgbSpace) {
    const [r, g, b] = (rgbSpace || '21 128 61').split(' ').map(Number);
    return '#' + [r, g, b].map((n) => n.toString(16).padStart(2, '0')).join('');
}
function hexToRgbSpace(hex) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `${r} ${g} ${b}`;
}
const primaryColorHex = computed({
    get: () => rgbSpaceToHex(form.primary_color),
    set: (hex) => (form.primary_color = hexToRgbSpace(hex)),
});

// Preview de logo/banner — mesmo padrão do formulário de produto.
// Sem isso, não havia NENHUM feedback visual de que o upload funcionou.
const logoPreview = ref(props.store.logo_url ?? null);
const bannerPreview = ref(props.store.banner_url ?? null);

function onLogoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.logo = file;
    logoPreview.value = URL.createObjectURL(file);
}
function onBannerChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.banner = file;
    bannerPreview.value = URL.createObjectURL(file);
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('tenant.store.update'), {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <h1 class="text-xl font-medium text-zinc-900 mb-1">Configurações da loja</h1>
    <p class="text-sm text-zinc-500 mb-6">Identidade, contato e regras de operação.</p>

    <form @submit.prevent="submit" class="max-w-2xl space-y-6">
        <div class="bg-white rounded-xl border border-zinc-200 p-5 space-y-4">
            <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Identidade</p>

            <div>
                <label class="block text-xs text-zinc-600 mb-1">Nome da loja</label>
                <input v-model="form.name" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="block text-xs text-zinc-600 mb-1">Descrição</label>
                <textarea v-model="form.description" rows="2" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm"></textarea>
            </div>

            <div>
                <label class="block text-xs text-zinc-600 mb-2">Cor da marca</label>
                <div class="flex items-center gap-3">
                    <input type="color" v-model="primaryColorHex" class="w-10 h-10 rounded-lg border border-zinc-300 cursor-pointer" />
                    <span class="text-sm text-zinc-600">{{ primaryColorHex }}</span>
                    <span class="text-xs text-zinc-400">Usada no hero e nos botões da vitrine</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Logo</label>
                    <div v-if="logoPreview" class="relative w-24 h-24">
                        <img :src="logoPreview" class="w-full h-full object-cover rounded-lg border border-zinc-200" />
                        <label class="absolute -bottom-2 -right-2 bg-white text-[10px] px-2 py-1 rounded-md cursor-pointer border border-zinc-300 shadow-sm">
                            Trocar
                            <input type="file" accept="image/*" class="hidden" @change="onLogoChange" />
                        </label>
                    </div>
                    <label v-else class="flex flex-col items-center justify-center gap-1 w-24 h-24 border-2 border-dashed border-zinc-300 rounded-lg cursor-pointer text-zinc-400 text-[10px] text-center px-1">
                        Toque para adicionar
                        <input type="file" accept="image/*" class="hidden" @change="onLogoChange" />
                    </label>
                </div>
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Banner</label>
                    <div v-if="bannerPreview" class="relative w-full h-24">
                        <img :src="bannerPreview" class="w-full h-full object-cover rounded-lg border border-zinc-200" />
                        <label class="absolute bottom-2 right-2 bg-white text-[10px] px-2 py-1 rounded-md cursor-pointer border border-zinc-300 shadow-sm">
                            Trocar
                            <input type="file" accept="image/*" class="hidden" @change="onBannerChange" />
                        </label>
                    </div>
                    <label v-else class="flex flex-col items-center justify-center gap-1 w-full h-24 border-2 border-dashed border-zinc-300 rounded-lg cursor-pointer text-zinc-400 text-xs">
                        Toque para adicionar
                        <input type="file" accept="image/*" class="hidden" @change="onBannerChange" />
                    </label>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 p-5 space-y-4">
            <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Contato</p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">WhatsApp para pedidos</label>
                    <input v-model="form.whatsapp_number" placeholder="5551999990000" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                    <p v-if="form.errors.whatsapp_number" class="text-xs text-red-500 mt-1">{{ form.errors.whatsapp_number }}</p>
                </div>
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">WhatsApp de contato (opcional)</label>
                    <input v-model="form.whatsapp_contact" placeholder="Se diferente do de pedidos" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                </div>
            </div>

            <div>
                <label class="block text-xs text-zinc-600 mb-1">Instagram</label>
                <input v-model="form.instagram_url" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
            </div>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 p-5 space-y-4">
            <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">Entrega e pedido mínimo</p>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Taxa de entrega (R$)</label>
                    <CurrencyInput v-model="form.delivery_fee_cents" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Pedido mínimo (R$)</label>
                    <CurrencyInput v-model="form.min_order_cents" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Tempo estimado (min)</label>
                    <input v-model="form.estimated_time_min" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" />
                </div>
            </div>

            <div class="flex gap-6 pt-1">
                <label class="flex items-center gap-2 text-sm text-zinc-700">
                    <input type="checkbox" v-model="form.accepts_delivery" /> Entrega
                </label>
                <label class="flex items-center gap-2 text-sm text-zinc-700">
                    <input type="checkbox" v-model="form.accepts_pickup" /> Retirada
                </label>
                <label class="flex items-center gap-2 text-sm text-zinc-700">
                    <input type="checkbox" v-model="form.accepts_dine_in" /> Consumo no local
                </label>
            </div>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="px-5 py-2.5 rounded-lg bg-primary text-white text-sm font-medium disabled:opacity-60"
        >
            {{ form.processing ? 'Salvando...' : 'Salvar alterações' }}
        </button>
        <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-emerald-600">Salvo com sucesso.</span>
    </form>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default { layout: AppLayout };
</script>