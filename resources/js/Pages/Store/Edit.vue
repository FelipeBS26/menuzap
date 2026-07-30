<script setup>
import { useForm } from '@inertiajs/vue3';
import CurrencyInput from '@/components/CurrencyInput.vue';

const props = defineProps({ store: Object });

const form = useForm({
    name: props.store.name,
    description: props.store.description ?? '',
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

function submit() {
    form.post(route('tenant.store.update'), {
        forceFormData: true,
        preserveScroll: true,
        transform: (data) => ({ ...data, _method: 'put' }),
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

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Logo</label>
                    <input type="file" accept="image/*" @change="form.logo = $event.target.files[0]" class="text-sm" />
                </div>
                <div>
                    <label class="block text-xs text-zinc-600 mb-1">Banner</label>
                    <input type="file" accept="image/*" @change="form.banner = $event.target.files[0]" class="text-sm" />
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