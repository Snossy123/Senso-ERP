function initPosTerminal() {
    if (window.posSystemInitialized) return;
    window.posSystemInitialized = true;

    // Use a Store for global accessibility (modals, sidebar, etc)
    Alpine.store('pos', {
        products: [],
        filteredProducts: [],
        cart: [],
        selectedCategory: 'all',
        searchQuery: '',
        customerId: '',
        paymentMethod: 'cash',
        amountTendered: 0,
        orderDiscount: 0,
        notes: '',
        processing: false,
        lastSaleId: 0,
        heldOrders: [],
        shiftId: null,
        shiftOpenFloat: 0,
        shiftCloseFloat: 0,
        shiftNotes: '',
        taxRate: 0,
        newCustomers: [],
        newCustomer: { name: '', phone: '', email: '', error: '' },
        activeProduct: null,
        activeProductVariants: [],
        currencySymbol: '$',
        routes: {},
        csrfToken: '',

        initStore(config) {
            if (config) {
                this.products = config.products || [];
                this.heldOrders = config.heldOrders || [];
                this.shiftId = config.shiftId || null;
                this.taxRate = config.taxRate || 0;
                this.currencySymbol = config.currencySymbol || '$';
                this.csrfToken = config.csrfToken || '';
                this.routes = config.routes || {};
                this.customers = config.customers || [];
            }
            this.applyFilter();
            this._registerKeyboardShortcuts();
        },

        _registerKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // F2 → focus search
                if (e.key === 'F2') {
                    e.preventDefault();
                    const el = document.getElementById('pos-search');
                    if (el) { el.focus(); el.select(); }
                }
                // F4 → open checkout (only if cart not empty and shift open)
                if (e.key === 'F4' && this.cart.length > 0 && this.shiftId) {
                    e.preventDefault();
                    $('#checkoutModal').modal('show');
                }
                // Escape on search → clear query
                if (e.key === 'Escape') {
                    const active = document.activeElement;
                    if (active && active.id === 'pos-search') {
                        this.searchQuery = '';
                        this.applyFilter();
                    }
                }
            });
        },

        async apiCall(url, options = {}) {
            const defaultOptions = {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json', 
                    'X-CSRF-TOKEN': this.csrfToken 
                }
            };
            try {
                const response = await fetch(url, { ...defaultOptions, ...options });
                const text = await response.text();
                let data = null;
                try { data = JSON.parse(text); } catch(e) {}
                if (!response.ok) throw data || { error: text || 'Server Error' };
                return data;
            } catch (err) {
                console.error("API Error:", err);
                throw err;
            }
        },

        applyFilter() {
            let list = this.products;
            if (this.selectedCategory !== 'all') {
                list = list.filter(p => p.category_id == this.selectedCategory);
            }
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(p => 
                    p.name.toLowerCase().includes(q) || 
                    (p.sku && p.sku.toLowerCase().includes(q)) || 
                    (p.barcode && p.barcode.toLowerCase().includes(q))
                );
            }
            this.filteredProducts = list;
        },

        onSearch() { this.applyFilter(); },

        barcodeSearch() {
            const q = this.searchQuery.trim();
            const exact = this.products.find(p => p.barcode === q || p.sku === q);
            if (exact) {
                this.addToCart(exact);
                this.searchQuery = '';
                // Flash the search input green
                const el = document.getElementById('pos-search');
                if (el) {
                    el.style.transition = 'background 0.1s';
                    el.style.background = '#d4edda';
                    setTimeout(() => { el.style.background = ''; }, 200);
                }
            }
            this.applyFilter();
        },

        addToCart(product) {
            if (!this.shiftId) { alert("Please open a shift first."); return; }
            if (product.out_of_stock) { alert('Out of stock.'); return; }
            if (product.has_variants && product.variants?.length > 0) {
                this.activeProduct = product; 
                this.activeProductVariants = product.variants;
                $('#variantModal').modal('show'); 
                return;
            }
            const existing = this.cart.find(i => i.id === product.id && !i.variant_id);
            if (existing) { 
                if (existing.qty < product.stock) existing.qty++; 
                else alert('Stock limit reached.'); 
            }
            else { 
                this.cart.push({ ...product, qty: 1, discount_pct: 0, variant_id: null }); 
            }
            this.scrollToBottomCart();
        },

        addVariantToCart(v) {
            const p = this.activeProduct; 
            if (!p) return;
            const existing = this.cart.find(i => i.id === p.id && i.variant_id === v.id);
            if (existing) { 
                if (existing.qty < p.stock) existing.qty++; 
                else alert('Stock limit reached.'); 
            }
            else { 
                this.cart.push({ 
                    id: p.id, 
                    name: p.name + ' - ' + v.name, 
                    price: v.price, 
                    stock: p.stock, 
                    qty: 1, 
                    discount_pct: 0, 
                    variant_id: v.id 
                }); 
            }
            $('#variantModal').modal('hide'); 
            this.scrollToBottomCart();
        },

        updateQty(idx, delta) {
            const item = this.cart[idx]; 
            if (!item) return;
            const newQty = item.qty + delta;
            if (newQty <= 0) this.cart.splice(idx, 1);
            else if (newQty > item.stock) alert('Stock limit reached.');
            else item.qty = newQty;
        },

        validateQty(idx) {
            const item = this.cart[idx];
            if(item.qty > item.stock) item.qty = item.stock;
            if(item.qty <= 0) item.qty = 1;
        },

        removeItem(idx) { this.cart.splice(idx, 1); },

        clearCart() { 
            if (confirm('Clear cart?')) this.clearCartState();
        },

        clearCartState() {
            this.cart = [];
            this.orderDiscount = 0;
            this.customerId = '';
            this.notes = '';
            this.paymentMethod = 'cash';
            this.amountTendered = 0;
        },

        scrollToBottomCart() { 
            setTimeout(() => { 
                const el = document.getElementById('cart-scroll-area'); 
                if(el) el.scrollTop = el.scrollHeight; 
            }, 100); 
        },

        itemTotal(i) { 
            const g = i.price * i.qty; 
            return g - (g * (i.discount_pct || 0) / 100); 
        },

        get subtotal() { 
            return this.cart.reduce((s, i) => s + this.itemTotal(i), 0); 
        },

        get totalDiscount() { 
            return (this.cart.reduce((s, i) => s + (i.price * i.qty * (i.discount_pct || 0) / 100), 0)) + (parseFloat(this.orderDiscount) || 0); 
        },

        get tax() { 
            return (this.subtotal - (parseFloat(this.orderDiscount) || 0)) * (this.taxRate / 100); 
        },

        get total() { 
            return Math.max(0, this.subtotal - (parseFloat(this.orderDiscount) || 0) + this.tax); 
        },

        get changeDue() {
            const tendered = parseFloat(this.amountTendered) || 0;
            const due = parseFloat(this.total) || 0;
            return tendered - due;
        },

        resetCheckout() { 
            this.paymentMethod = 'cash'; 
            this.amountTendered = this.total.toFixed(2); 
        },

        addTendered(amount) { 
            this.amountTendered = (parseFloat(this.amountTendered || 0) + amount).toFixed(2); 
        },

        async processPayment() {
            if (this.cart.length === 0) return;
            this.processing = true;
            try {
                const data = await this.apiCall(this.routes.storeSale, {
                    body: JSON.stringify({
                        items: this.cart.map(i => ({ id: i.id, qty: i.qty, price: i.price, discount_pct: i.discount_pct || 0, variant_id: i.variant_id })),
                        payment_method: this.paymentMethod, 
                        discount: this.orderDiscount, 
                        tax_rate: this.taxRate,
                        amount_tendered: this.paymentMethod === 'cash' ? this.amountTendered : this.total,
                        customer_id: this.customerId || null, 
                        shift_id: this.shiftId, 
                        notes: this.notes,
                    })
                });
                if (data.success) { 
                    this.lastSaleId = data.sale_id;
                    this.lastChangeDue = data.change_due || this.changeDue;
                    $('#checkoutModal').modal('hide');
                    setTimeout(() => $('#successModal').modal('show'), 400);
                }
            } catch (err) { 
                alert('Error: ' + (err.error || err.message)); 
            } finally { 
                this.processing = false; 
            }
        },

        async saveQuickCustomer() {
            if (!this.newCustomer.name) return;
            try {
                const data = await this.apiCall(this.routes.quickCustomer, { 
                    body: JSON.stringify(this.newCustomer) 
                });
                if (data.success) { 
                    this.newCustomers.push(data.customer); 
                    this.customerId = data.customer.id; 
                    $('#quickCustomerModal').modal('hide'); 
                }
            } catch (err) { 
                this.newCustomer.error = err.error || 'Error'; 
            }
        },

        async holdCurrentOrder() {
            if (this.cart.length === 0) return;
            const label = prompt('Label:') || '';
            try {
                const data = await this.apiCall(this.routes.holdOrder, { 
                    body: JSON.stringify({ cart: this.cart, label }) 
                });
                if (data.success) { 
                    this.heldOrders.unshift(data.held); 
                    this.cart = []; 
                    alert('Order held successfully.'); 
                }
            } catch (err) { 
                alert('Error holding order.'); 
            }
        },

        async resumeOrder(id) {
            try {
                const data = await this.apiCall(this.routes.resumeOrder.replace(':id', id), { 
                    method: 'POST' 
                });
                if (data.success) { 
                    this.cart = data.cart; 
                    this.heldOrders = this.heldOrders.filter(h => h.id !== id); 
                    $('#heldOrdersModal').modal('hide'); 
                }
            } catch (err) { 
                alert('Error resuming order.'); 
            }
        },

        async openShift() {
            try {
                const data = await this.apiCall(this.routes.openShift, { 
                    body: JSON.stringify({ opening_float: this.shiftOpenFloat }) 
                });
                if (data.success) window.location.reload();
            } catch (err) { 
                alert('Error opening shift.'); 
            }
        },

        async closeShift() {
            if (!confirm('Are you sure you want to close the register?')) return;
            try {
                const data = await this.apiCall(this.routes.closeShift.replace(':id', this.shiftId), { 
                    body: JSON.stringify({ closing_float: this.shiftCloseFloat, notes: this.shiftNotes }) 
                });
                if (data.success) window.location.reload();
            } catch (err) { 
                alert('Error closing shift.'); 
            }
        }
    });
}

if (window.Alpine) {
    initPosTerminal();
} else {
    document.addEventListener('alpine:init', initPosTerminal);
}
