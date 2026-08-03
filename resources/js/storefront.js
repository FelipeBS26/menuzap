import Alpine from 'alpinejs';

// Store global do carrinho — persistido em localStorage, 100% client-side
// (Fase 4). Guarda IDs (produto, tamanho, seleções), não rótulos de texto —
// os rótulos são calculados na hora de exibir, olhando o catálogo. Isso é
// o que permite reabrir um item do carrinho no modal de edição (Fase 8)
// com as escolhas exatas que o cliente fez.
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

    updateItem(index, item) {
        this.items[index] = item;
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

Alpine.data('storefront', (initialCategoryId) => ({
    // ---------- Navegação ----------
    activeCategory: initialCategoryId,

    // ---------- Catálogo e config da loja (JSON embutido na página) ----------
    productsById: {},
    storeConfig: { deliveryFeeCents: 0, minOrderCents: 0 },

    init() {
        const catalog = window.__CATALOG__ || [];
        catalog.forEach((category) => {
            category.products.forEach((product) => {
                this.productsById[product.id] = product;
            });
        });
        this.storeConfig = window.__STORE__ || this.storeConfig;
    },

    // ---------- Modal de personalização ----------
    open: false,
    product: null,
    selectedSizeId: null,
    selections: {}, // { groupId: [itemId, itemId, ...] }
    quantity: 1,
    notes: '',
    editingCartIndex: null, // null = adicionando novo · número = editando item existente

    openModal(productId) {
        const product = this.productsById[productId];
        if (!product) return;

        this.product = product;
        this.selectedSizeId = product.has_sizes && product.sizes.length ? product.sizes[0].id : null;
        this.selections = {};
        product.option_groups.forEach((g) => (this.selections[g.id] = []));
        this.quantity = 1;
        this.notes = '';
        this.editingCartIndex = null;
        this.open = true;
    },

    // Reabre o modal com as escolhas exatas de um item já no carrinho —
    // o cliente não é punido por querer mudar de ideia (Fase 8).
    editCartItem(index) {
        const item = this.$store.cart.items[index];
        const product = this.productsById[item.productId];
        if (!product) return;

        this.product = product;
        this.selectedSizeId = item.sizeId;
        this.selections = JSON.parse(JSON.stringify(item.selections));
        this.quantity = item.quantity;
        this.notes = item.notes;
        this.editingCartIndex = index;
        this.cartOpen = false;
        this.open = true;
    },

    closeModal() {
        this.open = false;
        this.editingCartIndex = null;
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

    isGroupValid(group) {
        const count = this.selections[group.id]?.length ?? 0;
        return count >= group.pivot.min_selections;
    },

    groupStatusLabel(group) {
        if (group.pivot.min_selections === 0) return 'Opcional';
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

        const cartItem = {
            productId: this.product.id,
            sizeId: this.selectedSizeId,
            selections: JSON.parse(JSON.stringify(this.selections)),
            notes: this.notes,
            quantity: this.quantity,
            unitPriceCents: this.unitPriceCents,
        };

        if (this.editingCartIndex !== null) {
            this.$store.cart.updateItem(this.editingCartIndex, cartItem);
        } else {
            this.$store.cart.addItem(cartItem);
        }

        this.closeModal();
    },

    // ---------- Carrinho (drawer) ----------
    cartOpen: false,

    openCart() {
        this.cartOpen = true;
    },
    closeCart() {
        this.cartOpen = false;
    },

    // Calcula os rótulos de exibição de um item do carrinho a partir do
    // catálogo — nunca fica desatualizado, mesmo que o nome do produto
    // mude depois do item já estar no carrinho.
    cartItemDisplay(item) {
        const product = this.productsById[item.productId];
        if (!product) return { name: 'Produto removido', sizeLabel: null, optionsLabel: [] };

        const sizeLabel = item.sizeId
            ? product.sizes.find((s) => s.id === item.sizeId)?.name
            : null;

        const optionsLabel = [];
        product.option_groups.forEach((g) => {
            (item.selections[g.id] || []).forEach((itemId) => {
                const optItem = g.items.find((i) => i.id === itemId);
                if (optItem) optionsLabel.push(optItem.name);
            });
        });

        return { name: product.name, sizeLabel, optionsLabel };
    },

    incrementCartQuantity(index) {
        this.$store.cart.items[index].quantity++;
        this.$store.cart.persist();
    },

    // Decrementar até 0 pede confirmação antes de remover — evita exclusão
    // acidental de um item que o cliente passou tempo personalizando (Fase 8).
    decrementCartQuantity(index) {
        const item = this.$store.cart.items[index];
        if (item.quantity > 1) {
            item.quantity--;
            this.$store.cart.persist();
        } else if (confirm('Remover este item do carrinho?')) {
            this.$store.cart.removeItem(index);
        }
    },

    removeCartItem(index) {
        if (confirm('Remover este item do carrinho?')) {
            this.$store.cart.removeItem(index);
        }
    },

    get cartMeetsMinimum() {
        return this.$store.cart.totalCents >= this.storeConfig.minOrderCents;
    },

    get amountMissingForMinimum() {
        return Math.max(0, this.storeConfig.minOrderCents - this.$store.cart.totalCents);
    },
}));

window.Alpine = Alpine;
Alpine.start();