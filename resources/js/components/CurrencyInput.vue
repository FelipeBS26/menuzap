<script setup>
import { ref, watch } from 'vue';

/**
 * Corrige um bug clássico do Vue: ligar v-model diretamente a uma
 * computed(get/set) que reformata o valor a cada tecla digitada faz o
 * cursor "pular" de posição a cada caractere — parece que os números
 * embaralham ou que o campo só soma um passo fixo, mas na verdade é o
 * DOM sendo reescrito no meio da digitação.
 *
 * A correção: o campo mantém um texto local livre enquanto o usuário
 * digita (sem reformatar nada), e só converte para centavos + reformata
 * a exibição no blur (quando o campo perde o foco). Recebe/emite sempre
 * em CENTAVOS (inteiro), consistente com o padrão do banco (Fase 5).
 */
const props = defineProps({
    modelValue: { type: Number, required: true },
});
const emit = defineEmits(['update:modelValue']);

function centsToDisplay(cents) {
    return (cents / 100).toFixed(2).replace('.', ',');
}

const display = ref(centsToDisplay(props.modelValue));

// Sincroniza se o valor mudar de fora (ex: form.reset(), carregar produto).
watch(
    () => props.modelValue,
    (val) => {
        const normalized = centsToDisplay(val);
        if (normalized !== display.value) display.value = normalized;
    }
);

function onBlur() {
    const cleaned = display.value.replace(/\./g, '').replace(',', '.');
    const parsed = parseFloat(cleaned) || 0;
    const cents = Math.max(0, Math.round(parsed * 100));
    display.value = centsToDisplay(cents);
    emit('update:modelValue', cents);
}
</script>

<template>
    <input type="text" inputmode="decimal" v-model="display" @blur="onBlur" />
</template>