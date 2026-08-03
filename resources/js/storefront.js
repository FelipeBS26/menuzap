import Alpine from 'alpinejs';

// Store global do carrinho — persistido em localStorage, 100% client-side
// (Fase 4). Nenhum estado de pedido é processado no servidor até o clique
// de finalizar, que só chega no Sprint 4.
Alpine.store('cart', {
    items: JSON.parse(localStorage.getItem('menuzap_cart') || '[]'),

    get count() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    get totalCents() {
        return this.items.reduce((sum, item) => sum + item.unitPriceCents * item.quantity, 0);
    },

    addItem(item) {
        this.items.push(item);
        this.persist();
    },

    removeItem(index) {
        this.items.splice(index, 1);
        this.persist();
    },

    persist() {
        localStorage.setItem('menuzap_cart', JSON.stringify(this.items));
    },
});

// Componente da página inteira: navegação por categoria + modal de
// personalização de produto. Um único x-data evita a complexidade de
// escopos aninhados do Alpine para algo que é, na prática, um estado só.
Alpine.data('storefront', (initialCategoryId) => ({
    // ---------- Navegação ----------
    activeCategory: initialCategoryId,

    // ---------- Catálogo (carregado do JSON embutido na página) ----------
    productsById: {},

    init() {
        const catalog = window.__CATALOG__ || [];
        catalog.forEach((category) => {
            category.products.forEach((product) => {
                this.productsById[product.id] = product;
            });
        });
    },

    // ---------- Modal de personalização ----------
    open: false,
    product: null,
    selectedSizeId: null,
    selections: {}, // { groupId: [itemId, itemId, ...] }
    quantity: 1,
    notes: '',

    openModal(productId) {
        const product = this.productsById[productId];
        if (!product) return;

        this.product = product;
        this.selectedSizeId = product.has_sizes && product.sizes.length ? product.sizes[0].id : null;
        this.selections = {};
        product.option_groups.forEach((g) => (this.selections[g.id] = []));
        this.quantity = 1;
        this.notes = '';
        this.open = true;
    },

    closeModal() {
        this.open = false;
    },

    toggleItem(group, item) {
        const sel = this.selections[group.id];
        const idx = sel.indexOf(item.id);

        if (idx > -1) {
            sel.splice(idx, 1);
            return;
        }

        // Seleção única (max = 1): marcar substitui o que já estava
        // marcado, em vez de acumular — comportamento de rádio, não checkbox.
        if (group.pivot.max_selections === 1) {
            this.selections[group.id] = [item.id];
        } else if (sel.length < group.pivot.max_selections) {
            sel.push(item.id);
        }
    },

    isItemSelected(group, item) {
        return (this.selections[group.id] || []).includes(item.id);
    },

    // Reforço positivo instantâneo (Fase 8): o grupo vira "OK" no exato
    // momento em que a regra de min_selections é satisfeita, sem esperar
    // o clique em "Adicionar".
    isGroupValid(group) {
        const count = this.selections[group.id]?.length ?? 0;
        return count >= group.pivot.min_selections;
    },

    groupStatusLabel(group) {
        if (group.pivot.min_selections === 0) return 'Opcional';
        const count = this.selections[group.id]?.length ?? 0;
        if (this.isGroupValid(group)) return '✓ Ok';
        if (group.pivot.min_selections === group.pivot.max_selections) {
            return `Escolha ${group.pivot.min_selections}`;
        }
        return `Escolha ao menos ${group.pivot.min_selections}`;
    },

    get allGroupsValid() {
        if (!this.product) return false;
        return this.product.option_groups.every((g) => this.isGroupValid(g));
    },

    get unitPriceCents() {
        if (!this.product) return 0;

        const base = this.product.has_sizes
            ? (this.product.sizes.find((s) => s.id === this.selectedSizeId)?.price_cents ?? 0)
            : this.product.base_price_cents;

        let extras = 0;
        this.product.option_groups.forEach((g) => {
            (this.selections[g.id] || []).forEach((itemId) => {
                const item = g.items.find((i) => i.id === itemId);
                if (item) extras += item.price_cents;
            });
        });

        return base + extras;
    },

    get totalCents() {
        return this.unitPriceCents * this.quantity;
    },

    formatPrice(cents) {
        return 'R$ ' + (cents / 100).toFixed(2).replace('.', ',');
    },

    addToCart() {
        if (!this.allGroupsValid) return;

        const sizeLabel = this.product.has_sizes
            ? this.product.sizes.find((s) => s.id === this.selectedSizeId)?.name
            : null;

        const optionsLabel = [];
        this.product.option_groups.forEach((g) => {
            (this.selections[g.id] || []).forEach((itemId) => {
                const item = g.items.find((i) => i.id === itemId);
                if (item) optionsLabel.push(item.name);
            });
        });

        this.$store.cart.addItem({
            productId: this.product.id,
            name: this.product.name,
            sizeLabel,
            optionsLabel,
            notes: this.notes,
            quantity: this.quantity,
            unitPriceCents: this.unitPriceCents,
        });

        this.closeModal();
    },
}));

window.Alpine = Alpine;
Alpine.start();