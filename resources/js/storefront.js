import Alpine from 'alpinejs';

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

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

// Fila offline-first (Fase 6): quando o POST /api/orders/log falha ou dá
// timeout, o pedido já foi enviado pelo WhatsApp (a venda nunca espera o
// nosso backend) — mas o registro fica pendente aqui, e tenta de novo
// sozinho na próxima vez que o cliente abrir a loja, sem ação nenhuma dele.
Alpine.store('pendingLogs', {
    items: JSON.parse(localStorage.getItem('menuzap_pending_logs') || '[]'),

    add(payload) {
        this.items.push(payload);
        this.persist();
    },

    remove(localId) {
        this.items = this.items.filter((p) => p.localId !== localId);
        this.persist();
    },

    persist() {
        localStorage.setItem('menuzap_pending_logs', JSON.stringify(this.items));
    },

    async retryAll(tenantSlug) {
        for (const payload of [...this.items]) {
            try {
                const { localId, ...body } = payload;
                const res = await fetch(`/api/${tenantSlug}/orders/log`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                    body: JSON.stringify(body),
                });
                if (res.ok) this.remove(localId);
            } catch (e) {
                // Continua tentando os próximos — um falhar não trava os outros.
            }
        }
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
        this.tenantSlug = window.__TENANT_SLUG__ || '';

        // Retry silencioso — se sobrou algum pedido não confirmado de uma
        // visita anterior (rede caiu no meio do POST), tenta de novo aqui,
        // sem o cliente precisar fazer nada (fila offline-first, Fase 6).
        if (this.$store.pendingLogs.items.length && this.tenantSlug) {
            this.$store.pendingLogs.retryAll(this.tenantSlug);
        }
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

    startNewOrder() {
        this.orderType = null;
        this.paymentMethod = '';
        this.changeForInput = '';
        this.lastOrderShortId = null;
        this.checkoutStep = 0;
        this.cartOpen = false;
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

    // Envio de verdade — a sequência obrigatória definida na Fase 6:
    // (1) preflight de status → (2) registrar e receber o short_id →
    // (3) montar a mensagem → (4) abrir o WhatsApp. Nenhuma etapa pode
    // travar a venda: falhas de rede em qualquer ponto degradam
    // graciosamente, nunca bloqueiam o clique final do cliente.
    submitting: false,
    lastOrderShortId: null,

    async submitOrder() {
        if (!this.canSubmitOrder || this.submitting) return;

        this.submitting = true;

        // (1) Preflight — confirma que a loja não fechou enquanto o
        // cliente preenchia o checkout (Fase 7). Se falhar/der timeout,
        // seguimos mesmo assim: a checagem é um bônus, não um bloqueio.
        try {
            const statusRes = await fetch(`/api/${this.tenantSlug}/store/status`, {
                signal: AbortSignal.timeout(2500),
            });
            const status = await statusRes.json();
            if (!status.is_open) {
                this.submitting = false;
                alert(status.message || 'A loja fechou enquanto você finalizava o pedido.');
                this.checkoutStep = 0;
                return;
            }
        } catch (e) {
            // Preflight indisponível — não impede a venda.
        }

        if (this.saveDataConsent) {
            this.$store.customer.save({
                name: this.customerName,
                phone: this.customerPhone,
                address: this.orderType === 'delivery' ? this.address : this.$store.customer.data.address,
            });
        }

        const payload = this.buildOrderPayload();
        const message = this.buildWhatsappMessage(payload);
        payload.whatsapp_message = message;

        let shortId = null;

        // (2) Registro com timeout de 2.5s (Fase 6).
        try {
            const res = await fetch(`/api/${this.tenantSlug}/orders/log`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                body: JSON.stringify(payload),
                signal: AbortSignal.timeout(2500),
            });
            if (res.ok) {
                const data = await res.json();
                shortId = data.short_id;
            }
        } catch (e) {
            // Timeout ou falha de rede — cai na degradação graciosa abaixo.
        }

        if (!shortId) {
            // Degradação graciosa: gera um ID local, guarda o registro
            // pendente para tentar de novo depois, e abre o WhatsApp de
            // qualquer forma. A pizzaria recebe o pedido mesmo se a nossa
            // infraestrutura falhar nesse segundo exato.
            shortId = 'LOCAL-' + Math.random().toString(36).slice(2, 6).toUpperCase();
            this.$store.pendingLogs.add({ ...payload, localId: shortId });
        }

        // (3) Mensagem final, com o short_id (oficial ou local) embutido.
        const finalMessage = message.replace('{{SHORT_ID}}', shortId);
        window.open(`https://wa.me/${this.storeConfig.whatsappNumber}?text=${encodeURIComponent(finalMessage)}`, '_blank');

        // (4) Limpa o carrinho e mostra a confirmação — o cliente nunca
        // fica se perguntando se o clique funcionou (Fase 8).
        this.$store.cart.clear();
        this.lastOrderShortId = shortId;
        this.checkoutStep = 3;
        this.submitting = false;
    },

    // Fonte única de verdade do pedido — o mesmo payload alimenta tanto o
    // POST /api/orders/log quanto a mensagem do WhatsApp. Nunca duas
    // funções paralelas montando o mesmo dado de formas diferentes (Fase 8).
    buildOrderPayload() {
        const items = this.$store.cart.items.map((item) => {
            const display = this.cartItemDisplay(item);
            return {
                name: display.name,
                size: display.sizeLabel,
                options: display.optionsLabel,
                notes: item.notes,
                quantity: item.quantity,
                unit_price_cents: item.unitPriceCents,
            };
        });

        const payload = {
            customer_name: this.customerName,
            customer_phone: this.customerPhone || null,
            order_type: this.orderType,
            payment_method: this.paymentMethod,
            total_cents: this.checkoutTotalCents,
            items_snapshot: items,
            address: { ...this.address },
        };

        // Cláusula de guarda (Fase 8): o endereço só viaja se o pedido for
        // de entrega. x-show apenas esconde o campo visualmente — os dados
        // continuam vivos no estado do Alpine por baixo, então essa remoção
        // explícita é obrigatória, não redundante.
        if (payload.order_type !== 'delivery') {
            delete payload.address;
        }

        return payload;
    },

    buildWhatsappMessage(payload) {
        const typeLabels = { delivery: 'Entrega', pickup: 'Retirada', dine_in: 'Consumo no local' };
        const paymentLabels = { pix: 'Pix', cash: 'Dinheiro', debit: 'Cartão de débito', credit: 'Cartão de crédito' };

        const lines = [];
        lines.push('Olá! Gostaria de fazer este pedido.');
        lines.push('Pedido #{{SHORT_ID}}');
        lines.push('');
        payload.items_snapshot.forEach((item) => {
            let line = `${item.quantity}x ${item.name}`;
            if (item.size) line += ` (${item.size})`;
            lines.push(line);
            if (item.options.length) lines.push(`  + ${item.options.join(', ')}`);
            if (item.notes) lines.push(`  Obs: ${item.notes}`);
        });
        lines.push('');
        lines.push(`Nome: ${payload.customer_name}`);
        if (payload.customer_phone) lines.push(`Telefone: ${payload.customer_phone}`);
        lines.push(`Tipo: ${typeLabels[payload.order_type]}`);

        if (payload.address) {
            const a = payload.address;
            lines.push(`Endereço: ${a.street}, ${a.number} - ${a.neighborhood}`);
            if (a.complement) lines.push(`Complemento: ${a.complement}`);
            if (a.reference) lines.push(`Referência: ${a.reference}`);
        }

        lines.push(`Pagamento: ${paymentLabels[payload.payment_method]}`);
        if (payload.payment_method === 'cash' && this.changeForCents > 0) {
            lines.push(`Troco para ${this.formatPrice(this.changeForCents)} (levar ${this.formatPrice(this.changeAmountCents)})`);
        }

        lines.push('');
        if (this.checkoutDeliveryFeeCents > 0) {
            lines.push(`Taxa de entrega: ${this.formatPrice(this.checkoutDeliveryFeeCents)}`);
        }
        lines.push(`Total: ${this.formatPrice(payload.total_cents)}`);

        return lines.join('\n');
    },

    // ---------- Início de sessão: retry silencioso de pedidos pendentes ----------
    tenantSlug: '',
}));

window.Alpine = Alpine;
Alpine.start();