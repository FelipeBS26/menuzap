import Alpine from 'alpinejs';

// Store global do carrinho — Alpine.store() fica acessível em qualquer
// componente da página via $store.cart, sem precisar passar props manualmente
// entre o hero, os cards e o modal (que chega na Parte 2).
// Decisão da Fase 4: carrinho 100% client-side, persistido em localStorage,
// nenhum estado de pedido processado no servidor até o clique de finalizar.
Alpine.store('cart', {
    items: JSON.parse(localStorage.getItem('menuzap_cart') || '[]'),

    get count() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    get totalCents() {
        return this.items.reduce((sum, item) => sum + item.unitPriceCents * item.quantity, 0);
    },

    persist() {
        localStorage.setItem('menuzap_cart', JSON.stringify(this.items));
    },

    // Métodos de adicionar/remover item chegam na Parte 2, junto do modal
    // de personalização — por ora o store só existe para o botão flutuante
    // já refletir count/total corretamente desde já.
});

window.Alpine = Alpine;
Alpine.start();