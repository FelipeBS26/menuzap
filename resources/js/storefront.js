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

    clear() {
        this.items = [];
        this.persist();
    },

    persist() {
        localStorage.setItem('menuzap_cart', JSON.stringify(this.items));
    },
});

// Store separado para os dados do cliente (nome, telefone, endereço) —
// persistidos entre visitas para pré-preencher o checkout (Fase 3/7).
// Deliberadamente separado do carrinho: limpar o carrinho depois de um
// pedido não deve apagar os dados de contato do cliente.
Alpine.store('customer', {
    data: JSON.parse(localStorage.getItem('menuzap_customer') || '{}'),

    save(data) {
        this.data = { ...this.data, ...data };
        localStorage.setItem('menuzap_customer', JSON.stringify(this.data));
    },

    clear() {
        this.data = {};
        localStorage.removeItem('menuzap_customer');
    },
});

Alpine.data('storefront', (initialCategoryId) => ({
    // ---------- Navegação ----------
    activeCategory: initialCategoryId,

    // ---------- Catálogo e config da loja (JSON embutido na página) ----------
    productsById: {},
    storeConfig: { deliveryFeeCents: 0, minOrderCents: 0, paymentMethods: [] },

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
    selections: {},
    quantity: 1,
    notes: '',
    editingCartIndex: null,

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

    // ---------- Carrinho + Checkout (mesmo drawer, etapas diferentes) ----------
    // checkoutStep: 0 = lista do carrinho · 1 = tipo de pedido · 2 = dados e pagamento
    // Decisão da Fase 8: o checkout NUNCA é uma tela separada — é o mesmo
    // painel mudando de conteúdo, sem fechar e reabrir.
    cartOpen: false,
    checkoutStep: 0,

    orderType: null, // 'delivery' | 'pickup' | 'dine_in'
    customerName: '',
    customerPhone: '',
    address: { street: '', number: '', neighborhood: '', complement: '', reference: '' },
    paymentMethod: '',
    changeForInput: '',
    saveDataConsent: true,

    openCart() {
        this.cartOpen = true;
        this.checkoutStep = 0;
    },
    closeCart() {
        this.cartOpen = false;
    },

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

    // ---------- Navegação entre etapas do checkout ----------
    goToCheckout() {
        if (!this.cartMeetsMinimum || !this.$store.cart.items.length) return;

        // Pré-preenche com dados salvos de uma visita anterior — o cliente
        // não redigita nome/endereço toda vez (Fase 3/7).
        const saved = this.$store.customer.data;
        this.customerName = saved.name || '';
        this.customerPhone = saved.phone || '';
        this.address = { street: '', number: '', neighborhood: '', complement: '', reference: '', ...(saved.address || {}) };
        this.paymentMethod = '';
        this.changeForInput = '';
        this.checkoutStep = 1;
    },

    backToCart() {
        this.checkoutStep = 0;
    },
    backToType() {
        this.checkoutStep = 1;
    },

    selectOrderType(type) {
        this.orderType = type;
        this.checkoutStep = 2;
    },

    get checkoutDeliveryFeeCents() {
        return this.orderType === 'delivery' ? this.storeConfig.deliveryFeeCents : 0;
    },
    get checkoutTotalCents() {
        return this.$store.cart.totalCents + this.checkoutDeliveryFeeCents;
    },

    // Matemática do troco (Fase 8): não deixa o cliente digitar um valor
    // menor que o total, e já calcula quanto o entregador precisa levar.
    get changeForCents() {
        const cleaned = (this.changeForInput || '').replace(/\./g, '').replace(',', '.');
        return Math.round((parseFloat(cleaned) || 0) * 100);
    },
    get changeAmountCents() {
        return Math.max(0, this.changeForCents - this.checkoutTotalCents);
    },
    get changeIsValid() {
        if (this.paymentMethod !== 'cash' || !this.changeForInput) return true;
        return this.changeForCents >= this.checkoutTotalCents;
    },

    get canSubmitOrder() {
        if (!this.orderType || !this.paymentMethod || !this.changeIsValid) return false;
        if (this.orderType !== 'dine_in' && !this.customerName.trim()) return false;
        if (this.orderType === 'delivery') {
            if (!this.address.street.trim() || !this.address.number.trim() || !this.address.neighborhood.trim()) {
                return false;
            }
        }
        return true;
    },

    // Envio de verdade (montar a mensagem, registrar o pedido, abrir o
    // WhatsApp) chega na Parte 3 — aqui só validamos e persistimos os
    // dados do cliente para a próxima visita.
    submitOrder() {
        if (!this.canSubmitOrder) return;

        if (this.saveDataConsent) {
            this.$store.customer.save({
                name: this.customerName,
                phone: this.customerPhone,
                address: this.orderType === 'delivery' ? this.address : this.$store.customer.data.address,
            });
        }

        alert('Validado! O envio de verdade (WhatsApp) chega na Parte 3 do Sprint 4.');
    },
}));

window.Alpine = Alpine;
Alpine.start();