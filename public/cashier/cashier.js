        const root = document.querySelector('[data-cashier]');
        const state = {
            products: [],
            employees: [],
            cart: new Map(),
            lastQuery: '',
            vehicleResults: [],
            customerDuplicate: false,
            transactions: [],
            refundSales: [],
            refundSelectedSale: null,
            refundProducts: [],
            refundReplacementCart: new Map(),
            refundReplacementDraft: new Map(),
            pricing: {
                tax_percentage: 0,
                service_fee_percentage: 0,
            },
            discount: null,
        };

        const els = {
            store: document.getElementById('store'),
            search: document.getElementById('search'),
            scan: document.getElementById('scan'),
            catalog: document.getElementById('catalog'),
            cart: document.getElementById('cart'),
            itemCount: document.getElementById('item-count'),
            subtotal: document.getElementById('subtotal'),
            discountCode: document.getElementById('discount-code'),
            applyDiscount: document.getElementById('apply-discount'),
            clearDiscount: document.getElementById('clear-discount'),
            discountRowDisplay: document.getElementById('discount-row-display'),
            discountTotal: document.getElementById('discount-total'),
            serviceFeeRate: document.getElementById('service-fee-rate'),
            serviceFeeTotal: document.getElementById('service-fee-total'),
            taxRate: document.getElementById('tax-rate'),
            taxTotal: document.getElementById('tax-total'),
            grandTotal: document.getElementById('grand-total'),
            change: document.getElementById('change'),
            debtRowDisplay: document.getElementById('debt-row-display'),
            debtTotal: document.getElementById('debt-total'),
            reset: document.getElementById('reset'),
            checkout: document.getElementById('checkout'),
            customerId: document.getElementById('customer-id'),
            customerName: document.getElementById('customer-name'),
            customerPhone: document.getElementById('customer-phone'),
            vehiclePlateNumber: document.getElementById('vehicle-plate-number'),
            vehicleMileage: document.getElementById('vehicle-mileage'),
            paymentMethod: document.getElementById('payment-method'),
            paidAmount: document.getElementById('paid-amount'),
            paidLabel: document.getElementById('paid-label'),
            paidField: document.getElementById('paid-field'),
            isDebt: document.getElementById('is-debt'),
            proofField: document.getElementById('proof-field'),
            paymentProof: document.getElementById('payment-proof'),
            splitToggle: document.getElementById('split-toggle'),
            splitPanel: document.getElementById('split-panel'),
            splitCash: document.getElementById('split-cash'),
            splitTransfer: document.getElementById('split-transfer'),
            splitQris: document.getElementById('split-qris'),
            splitTotal: document.getElementById('split-total'),
            toast: document.getElementById('toast'),
        };

        const money = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        });

        const debounce = (fn, wait = 250) => {
            let timeout;

            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => fn(...args), wait);
            };
        };

        const rupiah = (value) => money.format(Number(value || 0));

        const formatDate = (value) => {
            if (!value) return '-';
            try {
                return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            } catch (e) {
                return value;
            }
        };

        const currencyDigits = (value) => String(value ?? '').replace(/\D/g, '');

        const formatCurrencyInput = (value) => {
            const digits = currencyDigits(value);

            return digits === '' ? '' : new Intl.NumberFormat('id-ID').format(Number(digits));
        };

        const currencyValue = (input) => Number(currencyDigits(input.value) || 0);

        // Pembayaran gabungan (split): kumpulkan baris non-nol dari input cash/transfer/qris.
        const splitPayments = () => ([
            ['cash', els.splitCash],
            ['transfer', els.splitTransfer],
            ['qris', els.splitQris],
        ])
            .filter(([, input]) => input)
            .map(([method, input]) => ({ method, amount: currencyValue(input) }))
            .filter((p) => p.amount > 0);

        const splitTotalValue = () => splitPayments().reduce((sum, p) => sum + p.amount, 0);

        const isSplitOn = () => Boolean(els.splitToggle?.checked);

        const clearSplit = () => {
            if (els.splitToggle) els.splitToggle.checked = false;
            [els.splitCash, els.splitTransfer, els.splitQris].forEach((input) => {
                if (input) input.value = '';
            });
        };

        const bindCurrencyInput = (input, onChange) => {
            input.addEventListener('input', () => {
                input.value = formatCurrencyInput(input.value);
                onChange();
            });
        };

        const showToast = (message, type = '') => {
            els.toast.innerHTML = type === 'success'
                ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> ${escapeHtml(message)}`
                : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> ${escapeHtml(message)}`;

            els.toast.className = `toast ${type}`.trim();
            els.toast.classList.remove('hidden');
            setTimeout(() => els.toast.classList.add('hidden'), 3200);
        };

        const productImage = (product) => product.image_url
            ? `<img src="${product.image_url}" alt="${escapeHtml(product.name)}">`
            : `<div class="product-fallback">${escapeHtml(product.name.charAt(0).toUpperCase())}</div>`;

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        // Kunci cart per kombinasi (product_id, employee_id). employee_id null/empty → `${productId}-`.
        // Catatan: item.id tetap product id; cartKey terpisah untuk Map & tombol.
        const cartKey = (productId, employeeId) => `${productId}-${employeeId ?? ''}`;
        const itemCartKey = (item) => cartKey(item.id, item.employee_id);
        // Total qty produk yang sama yang tersebar di beberapa baris petugas, selain baris `excludeKey`.
        const otherProductQty = (productId, excludeKey) => Array.from(state.cart.values())
            .filter((i) => Number(i.id) === Number(productId) && itemCartKey(i) !== excludeKey)
            .reduce((sum, i) => sum + Number(i.quantity || 0), 0);

        const loadProducts = async () => {
            const params = new URLSearchParams({
                store_id: els.store.value,
                q: els.search.value,
                _: Date.now(),
            });

            const response = await fetch(`${root.dataset.productsUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });

            if (!response.ok) {
                showToast('Gagal memuat produk', 'error');
                return;
            }

            const data = await response.json();
            state.products = data.products;
            renderCatalog();
        };

        const loadProduct = async (productId) => {
            const params = new URLSearchParams({
                store_id: els.store.value,
                product_id: productId,
                _: Date.now(),
            });

            const response = await fetch(`${root.dataset.productsUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('Gagal memuat produk terbaru');
            }

            const data = await response.json();
            const product = data.products?.[0];

            if (!product) {
                throw new Error('Produk tidak ditemukan');
            }

            state.products = [
                product,
                ...state.products.filter((item) => Number(item.id) !== Number(product.id)),
            ];

            return product;
        };

        // Muat daftar petugas (Employee aktif) untuk picker per baris cart. Aman bila kosong.
        const loadEmployees = async () => {
            if (!root.dataset.employeesUrl || !els.store.value) {
                state.employees = [];
                return;
            }

            try {
                const params = new URLSearchParams({
                    store_id: els.store.value,
                    q: '',
                });

                const response = await fetch(`${root.dataset.employeesUrl}?${params}`, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    state.employees = [];
                    return;
                }

                const data = await response.json();
                state.employees = Array.isArray(data.employees) ? data.employees : [];
            } catch (error) {
                state.employees = [];
            }
        };

        const renderCatalog = () => {
            if (state.products.length === 0) {
                els.catalog.innerHTML = `
                    <div class="empty" style="grid-column: 1 / -1; min-height: 300px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:64px;height:64px;margin-bottom:16px;color:var(--line);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <p style="font-size: 16px; font-weight: 600;">Produk tidak ditemukan</p>
                        <span style="font-size: 14px; margin-top: 8px;">Coba kata kunci lain atau periksa kembali ejaan Anda.</span>
                    </div>`;
                return;
            }

            els.catalog.innerHTML = state.products.map((product) => {
                const isService = product.product_type === 'service';
                const out = !isService && product.stock <= 0;
                const low = !isService && product.stock < 5;
                const stockText = isService ? 'Jasa' : `Stok ${product.stock} ${escapeHtml(product.unit || 'pcs')}`;
                return `
                <button class="product" type="button" data-add="${product.id}" ${out ? 'disabled' : ''}>
                    <div class="product-thumb">${productImage(product)}</div>
                    <div class="product-body">
                        <div class="product-name">${escapeHtml(product.name)}</div>
                        <div class="product-code">${escapeHtml(product.code || '-')}</div>
                    </div>
                    <div class="product-foot">
                        <span class="price">${rupiah(product.price)}</span>
                        <span class="stock ${low ? 'low' : ''} ${out ? 'out' : ''}">${stockText}</span>
                    </div>
                    <span class="add-pill" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </span>
                </button>`;
            }).join('');
        };

        const roundMoney = (value) => Math.round(Number(value || 0) * 100) / 100;
        const chargeType = (type) => type === 'percentage' ? 'percentage' : 'fixed';
        const chargeValue = (value, type) => {
            const numeric = Math.max(0, Number(value || 0));

            return chargeType(type) === 'percentage' ? Math.min(100, numeric) : numeric;
        };
        const chargeAmount = (basePrice, type, value) => {
            const safeValue = chargeValue(value, type);

            return chargeType(type) === 'percentage'
                ? roundMoney(Number(basePrice || 0) * safeValue / 100)
                : roundMoney(safeValue);
        };
        const syncItemCharges = (item) => {
            item.tax_type = chargeType(item.tax_type || item.product_tax_type);
            item.service_fee_type = chargeType(item.service_fee_type || item.product_service_fee_type);
            item.tax_value = chargeValue(item.tax_value, item.tax_type);
            item.service_fee_value = chargeValue(item.service_fee_value, item.service_fee_type);
            item.tax_amount = chargeAmount(item.base_price, item.tax_type, item.tax_value);
            item.service_fee_amount = chargeAmount(item.base_price, item.service_fee_type, item.service_fee_value);

            return item;
        };
        const chargeLabel = (type) => chargeType(type) === 'percentage' ? '%' : 'Rp';
        const chargeInput = (item, kind) => {
            const type = kind === 'tax' ? item.tax_type : item.service_fee_type;
            const value = kind === 'tax' ? item.tax_value : item.service_fee_value;
            const max = chargeType(type) === 'percentage' ? ' max="100"' : '';
            const suffix = chargeLabel(type);
            const key = itemCartKey(item);

            return `
                <div class="charge-input-wrap">
                    <input class="input charge-input has-suffix" type="number" min="0"${max} value="${Number(value || 0)}" data-charge-${kind}="${key}">
                    <span class="charge-suffix">${suffix}</span>
                </div>
            `;
        };
        const productChargePayload = (product) => ({
            base_price: Number(product.base_price || product.price || 0),
            service_fee_type: chargeType(product.product_service_fee_type),
            service_fee_value: Number(product.product_service_fee_value ?? product.product_service_fee ?? product.service_fee_amount ?? 0),
            service_fee_amount: Number(product.product_service_fee || product.service_fee_amount || 0),
            tax_type: chargeType(product.product_tax_type),
            tax_value: Number(product.product_tax_value ?? product.product_tax_amount ?? product.tax_amount ?? 0),
            tax_amount: Number(product.product_tax_amount || product.tax_amount || 0),
        });
        const applyProductCharges = (item, productCharges) => {
            item.base_price = productCharges.base_price;
            item.tax_type = productCharges.tax_type;
            item.service_fee_type = productCharges.service_fee_type;
            if (!item.tax_dirty) {
                item.tax_value = productCharges.tax_value;
                item.tax_amount = productCharges.tax_amount;
            }
            if (!item.service_fee_dirty) {
                item.service_fee_value = productCharges.service_fee_value;
                item.service_fee_amount = productCharges.service_fee_amount;
            }

            return syncItemCharges(item);
        };

        const addProduct = async (productId) => {
            let product = state.products.find((item) => Number(item.id) === Number(productId));

            try {
                product = await loadProduct(productId);
            } catch (error) {
                if (!product) {
                    showToast(error.message || 'Produk tidak ditemukan', 'error');
                    return;
                }
            }

            if (!product) {
                showToast('Produk tidak ditemukan', 'error');
                return;
            }

            const productCharges = productChargePayload(product);
            const key = cartKey(product.id, null);
            const item = state.cart.get(key) || {
                ...product,
                ...productCharges,
                quantity: 0,
                employee_id: null,
                employee_name: null,
                charge_open: false,
                charge_dirty: false,
                tax_dirty: false,
                service_fee_dirty: false,
            };
            applyProductCharges(item, productCharges);

            // Stok dihitung lintas seluruh baris dengan product_id sama (bisa tersebar di beberapa petugas).
            if (product.product_type !== 'service'
                && otherProductQty(product.id, key) + item.quantity + 1 > product.stock) {
                showToast(`Stok ${product.name} tidak cukup`, 'error');
                return;
            }

            item.quantity += 1;
            state.cart.set(key, item);
            renderOrder();
        };

        const itemUnitPrice = (item) => Number(item.base_price || 0)
            + Number(item.service_fee_amount || 0)
            + Number(item.tax_amount || 0);

        const itemLineTotal = (item) => itemUnitPrice(item) * Number(item.quantity || 0);

        const changeQty = (key, delta) => {
            const item = state.cart.get(key);
            if (!item) return;

            const nextQty = item.quantity + delta;
            if (nextQty <= 0) {
                state.cart.delete(key);
            } else if (item.product_type === 'service'
                || otherProductQty(item.id, key) + nextQty <= item.stock) {
                item.quantity = nextQty;
                state.cart.set(key, item);
            }

            renderOrder();
        };

        const subtotal = () => Array.from(state.cart.values())
            .reduce((total, item) => total + itemLineTotal(item), 0);

        const calculateTotals = () => {
            const rawSubtotal = subtotal();
            let discountTotal = 0;

            if (state.discount) {
                const value = Number(state.discount.value || 0);
                discountTotal = state.discount.type === 'percentage'
                    ? rawSubtotal * Math.min(value, 100) / 100
                    : value;
                discountTotal = Math.min(rawSubtotal, Math.max(0, discountTotal));
            }

            const base = Math.max(0, rawSubtotal - discountTotal);
            const serviceFeeTotal = base * Number(state.pricing.service_fee_percentage || 0) / 100;
            const taxTotal = base * Number(state.pricing.tax_percentage || 0) / 100;

            return {
                subtotal: rawSubtotal,
                discountTotal,
                serviceFeeTotal,
                taxTotal,
                grandTotal: base + serviceFeeTotal + taxTotal,
            };
        };

        const loadPricing = async (discountCode = '') => {
            const params = new URLSearchParams({
                store_id: els.store.value,
                subtotal: subtotal(),
            });

            if (discountCode) {
                params.set('discount_code', discountCode);
            }

            const response = await fetch(`${root.dataset.pricingUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Kode diskon tidak valid');
            }

            state.pricing.tax_percentage = Number(data.pricing.tax_percentage || 0);
            state.pricing.service_fee_percentage = Number(data.pricing.service_fee_percentage || 0);

            if (data.pricing.discount_code) {
                state.discount = {
                    code: data.pricing.discount_code,
                    name: data.pricing.discount_name,
                    type: data.pricing.discount_type,
                    value: Number(data.pricing.discount_value || 0),
                };
            }

            renderOrder();

            return data.pricing;
        };

        const applyDiscount = async () => {
            const code = els.discountCode.value.trim().toUpperCase();

            if (!code) {
                state.discount = null;
                renderOrder();
                return;
            }

            try {
                const pricing = await loadPricing(code);
                els.discountCode.value = pricing.discount_code || code;
                showToast('Kode diskon diterapkan', 'success');
            } catch (error) {
                state.discount = null;
                showToast(error.message, 'error');
                renderOrder();
            }
        };

        const clearDiscount = (showMessage = true) => {
            state.discount = null;
            els.discountCode.value = '';
            if (showMessage) showToast('Kode diskon dihapus', 'success');
            renderOrder();
        };

        // Picker petugas per baris cart. Maks satu petugas (select tunggal). Kosong = tanpa petugas.
        const mechanicSelect = (item, key) => {
            const options = ['<option value="">— Petugas —</option>']
                .concat((state.employees || []).map((emp) => {
                    const label = emp.code ? `${emp.name} (${emp.code})` : emp.name;
                    const selected = item.employee_id !== null && item.employee_id !== undefined
                        && Number(item.employee_id) === Number(emp.id) ? ' selected' : '';
                    return `<option value="${emp.id}"${selected}>${escapeHtml(label)}</option>`;
                }))
                .join('');

            return `<select class="mechanic-select" data-mechanic="${key}">${options}</select>`;
        };

        const renderOrder = () => {            const items = Array.from(state.cart.values());
            root.classList.toggle('cart-empty', items.length === 0);
            if (els.vehiclePlateNumber && !els.vehiclePlateNumber.value.trim()) {
                const box = document.getElementById('vehicle-service-info');
                if (box) { box.style.display = 'none'; box.innerHTML = ''; }
            }

            els.itemCount.textContent = items.reduce((total, item) => total + item.quantity, 0);

            if (items.length === 0) {
                els.cart.innerHTML = `
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <p>Keranjang Kosong</p>
                        <span style="font-size: 13px; margin-top: 6px;">Pilih produk dari katalog untuk memulai.</span>
                    </div>`;
            } else {
                els.cart.innerHTML = items.map((item) => {
                    const key = itemCartKey(item);
                    return `
                    <div class="cart-item" data-cart-item="${key}">
                        <div class="cart-title-row">
                            <div style="min-width: 0; padding-right: 8px; flex: 1;">
                                <div class="cart-title">${escapeHtml(item.name)}</div>
                                <div class="item-total" data-line-total style="margin-top: 4px;">${rupiah(itemLineTotal(item))}</div>
                                <div class="cart-meta" data-unit-price>${rupiah(itemUnitPrice(item))} / item</div>
                            </div>
                            <button class="icon-btn remove" type="button" data-remove="${key}" title="Hapus">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <div class="cart-mechanic-row">
                            ${mechanicSelect(item, key)}
                        </div>
                        <div class="cart-control-row">
                            <div class="qty">
                                <button class="icon-btn" type="button" data-dec="${key}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                </button>
                                <strong>${item.quantity}</strong>
                                <button class="icon-btn" type="button" data-inc="${key}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                </button>
                            </div>
                            <button class="charge-btn" type="button" data-toggle-charges="${key}">Edit biaya</button>
                        </div>
                        ${item.charge_open ? `
                            <div class="charge-grid">
                                <label class="field">
                                    <span>Tax / item</span>
                                    ${chargeInput(item, 'tax')}
                                </label>
                                <label class="field">
                                    <span>Service fee / item</span>
                                    ${chargeInput(item, 'service')}
                                </label>
                            </div>
                        ` : ''}
                    </div>
                `;
                }).join('');
            }

            renderPaymentSummary(items);
        };

        const renderPaymentSummary = (items = Array.from(state.cart.values())) => {
            const totals = calculateTotals();
            const splitOn = isSplitOn();

            if (els.splitPanel) els.splitPanel.style.display = splitOn ? 'flex' : 'none';
            if (splitOn && els.splitTotal) els.splitTotal.textContent = rupiah(splitTotalValue());

            const paid = splitOn
                ? splitTotalValue()
                : (els.isDebt.checked
                    ? currencyValue(els.paidAmount)
                    : (els.paymentMethod.value === 'qris' ? totals.grandTotal : currencyValue(els.paidAmount)));
            const debt = els.isDebt.checked ? Math.max(0, totals.grandTotal - paid) : 0;
            els.subtotal.textContent = rupiah(totals.subtotal);
            els.discountTotal.textContent = `- ${rupiah(totals.discountTotal)}`;
            els.serviceFeeRate.textContent = `(${Number(state.pricing.service_fee_percentage || 0)}%)`;
            els.serviceFeeTotal.textContent = rupiah(totals.serviceFeeTotal);
            els.taxRate.textContent = `(${Number(state.pricing.tax_percentage || 0)}%)`;
            els.taxTotal.textContent = rupiah(totals.taxTotal);
            els.grandTotal.textContent = rupiah(totals.grandTotal);
            els.change.textContent = rupiah(els.isDebt.checked ? 0 : Math.max(0, paid - totals.grandTotal));
            els.debtTotal.textContent = rupiah(debt);
            els.discountRowDisplay.style.display = totals.discountTotal > 0 ? 'flex' : 'none';
            els.debtRowDisplay.style.display = els.isDebt.checked ? 'flex' : 'none';
            els.checkout.disabled = items.length === 0;
            els.paidLabel.textContent = els.isDebt.checked ? 'Nominal Sudah Dibayar' : 'Nominal Diterima';
            els.paidField.style.display = (! splitOn && (els.paymentMethod.value === 'cash' || els.isDebt.checked)) ? 'flex' : 'none';
            const splitNonCash = splitOn && (currencyValue(els.splitTransfer) + currencyValue(els.splitQris)) > 0;
            els.proofField.style.display = (els.paymentMethod.value === 'qris' || splitNonCash) ? 'flex' : 'none';
        };

        const checkout = async () => {
            const hasDuplicate = await validateCustomerDuplicate(true);
            if (hasDuplicate) return;

            const totals = calculateTotals();
            const debtChecked = els.isDebt.checked;
            const hasCustomer = Boolean(els.customerId.value || els.customerName.value.trim() || els.customerPhone.value.trim());

            if (debtChecked && !hasCustomer) {
                showToast('Transaksi hutang wajib memilih atau membuat pelanggan', 'error');
                return;
            }

            const payload = new FormData();

            payload.append('store_id', els.store.value);
            if (els.customerId.value) payload.append('customer_id', els.customerId.value);
            if (els.customerName.value) payload.append('customer_name', els.customerName.value);
            if (els.customerPhone.value) payload.append('customer_phone', els.customerPhone.value);
            if (els.vehiclePlateNumber.value) payload.append('vehicle_plate_number', els.vehiclePlateNumber.value);
            if (els.vehicleMileage.value) payload.append('vehicle_mileage', els.vehicleMileage.value);
            const splitOn = isSplitOn();
            const splitRows = splitOn ? splitPayments() : [];

            if (splitOn && splitRows.length === 0) {
                showToast('Isi minimal satu nominal pembayaran', 'error');
                return;
            }

            if (splitOn) {
                const paidNow = splitTotalValue();
                if (!debtChecked && paidNow < totals.grandTotal) {
                    showToast('Total pembayaran kurang dari tagihan', 'error');
                    return;
                }
                payload.append('payment_method', splitRows.length > 1 ? 'split' : splitRows[0].method);
                splitRows.forEach((p, i) => {
                    payload.append(`payments[${i}][method]`, p.method);
                    payload.append(`payments[${i}][amount]`, p.amount);
                });
                payload.append('paid_amount', paidNow);
            } else {
                payload.append('payment_method', els.paymentMethod.value);
                payload.append('paid_amount', debtChecked
                    ? currencyValue(els.paidAmount)
                    : (els.paymentMethod.value === 'qris' ? totals.grandTotal : currencyValue(els.paidAmount)));
            }

            payload.append('is_debt', debtChecked ? '1' : '0');
            if (state.discount?.code) payload.append('discount_code', state.discount.code);

            // Bukti transfer: wajib untuk QRIS tunggal; opsional untuk mode split.
            if (!splitOn && els.paymentMethod.value === 'qris' && els.paymentProof.files.length === 0) {
                showToast('Upload bukti transfer untuk pembayaran QRIS', 'error');
                renderOrder();
                return;
            }
            if (els.paymentProof.files.length > 0) {
                payload.append('payment_proof', els.paymentProof.files[0]);
            }

            Array.from(state.cart.values()).forEach((item, index) => {
                payload.append(`items[${index}][product_id]`, item.id);
                payload.append(`items[${index}][quantity]`, item.quantity);
                payload.append(`items[${index}][tax_amount]`, Number(item.tax_amount || 0));
                payload.append(`items[${index}][service_fee_amount]`, Number(item.service_fee_amount || 0));
                if (item.employee_id !== null && item.employee_id !== undefined && item.employee_id !== '') {
                    payload.append(`items[${index}][employee_id]`, item.employee_id);
                }
            });

            els.checkout.disabled = true;

            try {
                const response = await fetch(root.dataset.checkoutUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf,
                    },
                    body: payload,
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Transaksi gagal');
                }

                showToast(`Order ${data.sale.number} selesai diproses!`, 'success');
                showReceipt(data.sale);
                state.cart.clear();
                els.customerId.value = '';
                els.customerName.value = '';
                els.customerPhone.value = '';
                els.vehiclePlateNumber.value = '';
                els.vehicleMileage.value = '';
                els.paidAmount.value = '';
                els.paymentProof.value = '';
                els.isDebt.checked = false;
                clearSplit();
                clearDiscount(false);
                await loadProducts();
                renderOrder();
            } catch (error) {
                showToast(error.message, 'error');
                renderOrder();
            }
        };

        els.catalog.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-add]');
            if (!button) return;

            await addProduct(Number(button.dataset.add));
        });

        els.cart.addEventListener('click', async (event) => {
            const inc = event.target.closest('[data-inc]');
            const dec = event.target.closest('[data-dec]');
            const remove = event.target.closest('[data-remove]');
            const toggleCharges = event.target.closest('[data-toggle-charges]');

            if (inc) changeQty(inc.dataset.inc, 1);
            if (dec) changeQty(dec.dataset.dec, -1);
            if (remove) {
                state.cart.delete(remove.dataset.remove);
                renderOrder();
            }
            if (toggleCharges) {
                const key = toggleCharges.dataset.toggleCharges;
                const item = state.cart.get(key);
                if (!item) return;

                try {
                    const product = await loadProduct(item.id);
                    applyProductCharges(item, productChargePayload(product));
                } catch (error) {
                    showToast(error.message || 'Gagal memuat produk terbaru', 'error');
                }

                item.charge_open = !item.charge_open;
                state.cart.set(key, item);
                renderOrder();
            }
        });

        els.cart.addEventListener('input', (event) => {
            const tax = event.target.closest('[data-charge-tax]');
            const service = event.target.closest('[data-charge-service]');
            const input = tax || service;
            if (!input) return;

            const item = state.cart.get(input.dataset.chargeTax || input.dataset.chargeService);
            if (!item) return;
            const readChargeInput = (element, type) => {
                if (element.value === '') {
                    return 0;
                }

                const numeric = Math.max(0, Number(element.value || 0));
                const value = chargeType(type) === 'percentage' ? Math.min(100, numeric) : numeric;

                if (numeric !== value || Number(element.value) < 0) {
                    element.value = value;
                }

                return value;
            };

            if (tax) {
                item.charge_dirty = true;
                item.tax_dirty = true;
                item.tax_value = readChargeInput(tax, item.tax_type);
            }

            if (service) {
                item.charge_dirty = true;
                item.service_fee_dirty = true;
                item.service_fee_value = readChargeInput(service, item.service_fee_type);
            }

            syncItemCharges(item);
            state.cart.set(itemCartKey(item), item);
            const row = input.closest('[data-cart-item]');
            row?.querySelector('[data-line-total]')?.replaceChildren(document.createTextNode(rupiah(itemLineTotal(item))));
            row?.querySelector('[data-unit-price]')?.replaceChildren(document.createTextNode(`${rupiah(itemUnitPrice(item))} / item`));
            renderPaymentSummary();
        });

        // Pemilihan petugas per baris cart. Mengubah employee_id mengubah cart key → reassign baris.
        els.cart.addEventListener('change', (event) => {
            const select = event.target.closest('[data-mechanic]');
            if (!select) return;

            const oldKey = select.dataset.mechanic;
            const item = state.cart.get(oldKey);
            if (!item) return;

            const value = select.value;
            const newEmployeeId = value === '' ? null : Number(value);
            const employee = newEmployeeId === null
                ? null
                : (state.employees || []).find((emp) => Number(emp.id) === newEmployeeId);

            state.cart.delete(oldKey);
            item.employee_id = newEmployeeId;
            item.employee_name = employee ? employee.name : null;

            const newKey = itemCartKey(item);
            const existing = state.cart.get(newKey);

            // Bila kombinasi (product_id, employee_id) sudah ada → gabungkan quantity, jangan gandakan.
            if (existing && existing !== item) {
                existing.quantity += item.quantity;
                state.cart.set(newKey, existing);
            } else {
                state.cart.set(newKey, item);
            }

            renderOrder();
        });

        els.search.addEventListener('input', debounce(loadProducts));
        els.store.addEventListener('change', () => {
            state.cart.clear();
            els.customerId.value = '';
            els.customerName.value = '';
            els.customerPhone.value = '';
            els.vehiclePlateNumber.value = '';
            els.vehicleMileage.value = '';
            state.customerDuplicate = false;
            state.discount = null;
            els.discountCode.value = '';
            els.isDebt.checked = false;
            renderOrder();
            loadProducts();
            loadEmployees().then(renderOrder);
            loadPricing().catch(() => { });
        });
        document.querySelectorAll('.payment-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                els.paymentMethod.value = card.dataset.method;
                renderOrder();
            });
        });

        bindCurrencyInput(els.paidAmount, renderOrder);
        els.isDebt.addEventListener('change', renderOrder);

        // Pembayaran gabungan (split).
        els.splitToggle?.addEventListener('change', renderOrder);
        [els.splitCash, els.splitTransfer, els.splitQris].forEach((input) => {
            if (input) bindCurrencyInput(input, renderOrder);
        });
        els.scan?.addEventListener('keydown', async (event) => {
            if (event.key !== 'Enter') return;

            const code = els.scan.value.trim().toLowerCase();
            const product = state.products.find((item) =>
                String(item.barcode || '').toLowerCase() === code ||
                String(item.sku || '').toLowerCase() === code
            );

            if (product) {
                await addProduct(product.id);
                els.scan.value = '';
                return;
            }

            els.search.value = code;
            loadProducts().then(async () => {
                const found = state.products.find((item) =>
                    String(item.barcode || '').toLowerCase() === code ||
                    String(item.sku || '').toLowerCase() === code
                );

                if (found) await addProduct(found.id);
                else showToast('Produk barcode tidak ditemukan', 'error');

                els.scan.value = '';
            });
        });
        const customerDuplicateCheck = debounce(() => validateCustomerDuplicate(true), 350);
        els.customerName.addEventListener('input', () => {
            els.customerId.value = '';
            customerDuplicateCheck();
        });
        els.customerPhone.addEventListener('input', () => {
            els.customerId.value = '';
            customerDuplicateCheck();
        });
        els.reset.addEventListener('click', () => {
            if (state.cart.size === 0) return;
            if (confirm('Kosongkan keranjang?')) {
                state.cart.clear();
                els.customerId.value = '';
                els.customerName.value = '';
                els.customerPhone.value = '';
                els.vehiclePlateNumber.value = '';
                els.vehicleMileage.value = '';
                els.paidAmount.value = '';
                els.paymentProof.value = '';
                els.isDebt.checked = false;
                clearSplit();
                clearDiscount(false);
                renderOrder();
            }
        });
        els.checkout.addEventListener('click', checkout);
        els.applyDiscount.addEventListener('click', applyDiscount);
        els.clearDiscount.addEventListener('click', clearDiscount);
        els.discountCode.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') applyDiscount();
        });

        const customerSearch = document.getElementById('customer-search');
        const customerList = document.getElementById('customer-list');
        const vehicleList = document.getElementById('vehicle-list');
        const customerManualModal = document.getElementById('customer-manual-modal');
        const manualVehiclePlateNumber = document.getElementById('manual-vehicle-plate-number');
        const manualVehicleMileage = document.getElementById('manual-vehicle-mileage');
        const vehicleManualModal = document.getElementById('vehicle-manual-modal');
        const vehicleOwnerId = document.getElementById('vehicle-owner-id');
        const newVehicleName = document.getElementById('new-vehicle-name');
        const newVehiclePlateNumber = document.getElementById('new-vehicle-plate-number');
        const vehicleOwnerName = document.getElementById('vehicle-owner-name');
        const vehicleOwnerPhone = document.getElementById('vehicle-owner-phone');
        const newVehicleMileage = document.getElementById('new-vehicle-mileage');
        const vehicleOwnerList = document.getElementById('vehicle-owner-list');
        const transactionModal = document.getElementById('transaction-modal');
        const transactionSearch = document.getElementById('transaction-search');
        const transactionList = document.getElementById('transaction-list');
        const refundModal = document.getElementById('refund-modal');
        const refundProductModal = document.getElementById('refund-product-modal');
        const refundSearch = document.getElementById('refund-search');
        const refundSaleList = document.getElementById('refund-sale-list');
        const refundType = document.getElementById('refund-type');
        const refundSelectedSale = document.getElementById('refund-selected-sale');
        const refundReturnList = document.getElementById('refund-return-list');
        const refundReplacementSection = document.getElementById('refund-replacement-section');
        const openRefundProducts = document.getElementById('open-refund-products');
        const refundProductSearch = document.getElementById('refund-product-search');
        const refundProductList = document.getElementById('refund-product-list');
        const refundReplacementCart = document.getElementById('refund-replacement-cart');
        const refundAdditionalPayment = document.getElementById('refund-additional-payment');
        const refundEvidence = document.getElementById('refund-evidence');
        const refundReason = document.getElementById('refund-reason');
        const refundSummary = document.getElementById('refund-summary');
        const processRefundButton = document.getElementById('process-refund');
        const receiptModal = document.getElementById('receipt-modal');
        const receiptContent = document.getElementById('receipt-content');
        const markSalePaidButton = document.getElementById('mark-sale-paid');
        let lastReceiptSale = null;
        let customersTimer;
        let vehiclesTimer;

        document.getElementById('btn-open-manual-customer').addEventListener('click', () => {
            customerList.style.display = 'none';
            if (!els.customerName.value && customerSearch.value.trim()) {
                els.customerName.value = customerSearch.value.trim();
            }
            customerManualModal.classList.remove('hidden');
            els.customerName.focus();
        });

        document.getElementById('close-customer-manual-modal').addEventListener('click', () => {
            customerManualModal.classList.add('hidden');
        });

        document.getElementById('btn-open-vehicle-modal').addEventListener('click', () => {
            vehicleList.style.display = 'none';
            vehicleOwnerList.style.display = 'none';
            vehicleOwnerId.value = els.customerId.value || '';
            newVehicleName.value = '';
            newVehiclePlateNumber.value = els.vehiclePlateNumber.value.trim();
            vehicleOwnerName.value = els.customerName.value.trim() || customerSearch.value.trim();
            vehicleOwnerPhone.value = els.customerPhone.value.trim();
            newVehicleMileage.value = els.vehicleMileage.value || '';
            vehicleManualModal.classList.remove('hidden');
            newVehicleName.focus();
        });

        document.getElementById('close-vehicle-manual-modal').addEventListener('click', () => {
            vehicleManualModal.classList.add('hidden');
        });

        document.getElementById('btn-apply-customer').addEventListener('click', async () => {
            const button = document.getElementById('btn-apply-customer');
            const name = els.customerName.value.trim();
            const phone = els.customerPhone.value.trim();
            const vehiclePlateNumber = manualVehiclePlateNumber.value.trim();
            const vehicleMileage = manualVehicleMileage.value;

            if (!name) {
                showToast('Nama pelanggan wajib diisi', 'error');
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch(root.dataset.customersStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf,
                    },
                    body: JSON.stringify({
                        store_id: els.store.value,
                        name,
                        phone,
                        vehicle_plate_number: vehiclePlateNumber,
                        vehicle_mileage: vehicleMileage || null,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Gagal menyimpan pelanggan');
                }

                selectCustomer(data.customer.id, data.customer.name, data.customer.phone || '');
                if (vehiclePlateNumber) {
                    els.vehiclePlateNumber.value = vehiclePlateNumber.toUpperCase();
                    els.vehicleMileage.value = vehicleMileage || '';
                }
                manualVehiclePlateNumber.value = '';
                manualVehicleMileage.value = '';
                customerManualModal.classList.add('hidden');
                showToast('Pelanggan baru tersimpan', 'success');
                renderOrder();
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                button.disabled = false;
            }
        });

        const loadVehicleOwners = async (query = '') => {
            vehicleOwnerList.style.display = 'flex';
            vehicleOwnerList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-light);">Memuat...</div>';

            try {
                const params = new URLSearchParams({ store_id: els.store.value, q: query });
                const res = await fetch(`${root.dataset.customersUrl}?${params}`, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message || 'Gagal memuat customer');
                }

                if ((data.customers || []).length === 0) {
                    vehicleOwnerList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-light);">Tidak ada customer ditemukan.</div>';
                    return;
                }

                vehicleOwnerList.innerHTML = data.customers.map((customer) => `
                    <div class="customer-item" onclick="selectVehicleOwner(${customer.id}, '${escapeHtml(customer.name)}', '${escapeHtml(customer.phone || '')}')">
                        <h4>${escapeHtml(customer.name)}</h4>
                        ${customer.phone ? `<p>${escapeHtml(customer.phone)}</p>` : ''}
                    </div>
                `).join('');
            } catch (error) {
                vehicleOwnerList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--danger);">Gagal memuat customer.</div>';
            }
        };

        document.getElementById('btn-search-vehicle-owner').addEventListener('click', () => {
            loadVehicleOwners('');
        });

        vehicleOwnerName.addEventListener('input', () => {
            vehicleOwnerId.value = '';
            vehicleOwnerList.style.display = 'none';
        });

        document.getElementById('btn-save-vehicle').addEventListener('click', async () => {
            const button = document.getElementById('btn-save-vehicle');
            const ownerName = vehicleOwnerName.value.trim();
            const plateNumber = newVehiclePlateNumber.value.trim();

            if (!ownerName) {
                showToast('Nama pemilik wajib diisi', 'error');
                return;
            }

            if (!plateNumber) {
                showToast('Nomor plat wajib diisi', 'error');
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch(root.dataset.vehiclesStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf,
                    },
                    body: JSON.stringify({
                        store_id: els.store.value,
                        customer_id: vehicleOwnerId.value || null,
                        owner_name: ownerName,
                        owner_phone: vehicleOwnerPhone.value.trim(),
                        vehicle_name: newVehicleName.value.trim(),
                        plate_number: plateNumber,
                        mileage: newVehicleMileage.value || null,
                    }),
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Gagal menyimpan kendaraan');
                }

                selectVehicle(
                    data.vehicle.id,
                    data.vehicle.plate_number,
                    data.vehicle.mileage || '',
                    data.vehicle.customer?.id || null,
                    data.vehicle.customer?.name || ownerName,
                    data.vehicle.customer?.phone || vehicleOwnerPhone.value.trim()
                );
                vehicleManualModal.classList.add('hidden');
                showToast('Kendaraan tersimpan', 'success');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                button.disabled = false;
            }
        });

        // Hide customer list when clicking outside
        document.addEventListener('click', (e) => {
            if (!customerSearch.contains(e.target) && !customerList.contains(e.target)) {
                customerList.style.display = 'none';
            }

            if (!els.vehiclePlateNumber.contains(e.target) && !vehicleList.contains(e.target)) {
                vehicleList.style.display = 'none';
            }

            if (!vehicleOwnerName.contains(e.target) && !vehicleOwnerList.contains(e.target) && !document.getElementById('btn-search-vehicle-owner').contains(e.target)) {
                vehicleOwnerList.style.display = 'none';
            }
        });

        customerSearch.addEventListener('focus', () => {
            if (customerSearch.value.trim() !== '') {
                customerList.style.display = 'flex';
            } else {
                loadCustomers();
            }
        });

        const loadCustomers = async (query = '') => {
            customerList.style.display = 'flex';
            customerList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-light);">Memuat...</div>';
            try {
                const params = new URLSearchParams({ store_id: els.store.value, q: query });
                const res = await fetch(`${root.dataset.customersUrl}?${params}`);
                const data = await res.json();

                if (data.customers.length === 0) {
                    customerList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-light);">Tidak ada pelanggan ditemukan.</div>';
                    return;
                }

                customerList.innerHTML = data.customers.map(c => `
                    <div class="customer-item" onclick="selectCustomer(${c.id}, '${escapeHtml(c.name)}', '${escapeHtml(c.phone || '')}')">
                        <h4>${escapeHtml(c.name)}</h4>
                        ${c.phone ? `<p>${escapeHtml(c.phone)}</p>` : ''}
                        ${Array.isArray(c.vehicles) && c.vehicles.length > 0 ? `<p>${c.vehicles.map((vehicle) => `${escapeHtml(vehicle.plate_number)}${vehicle.mileage ? ` (${vehicle.mileage} km)` : ''}`).join(', ')}</p>` : ''}
                    </div>
                `).join('');
            } catch (err) {
                customerList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--danger);">Gagal memuat pelanggan.</div>';
            }
        };

        customerSearch.addEventListener('input', (e) => {
            clearTimeout(customersTimer);
            customersTimer = setTimeout(() => {
                loadCustomers(e.target.value);
            }, 300);
        });

        const loadVehicles = async (query = '') => {
            vehicleList.style.display = 'flex';
            vehicleList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-light);">Memuat...</div>';

            try {
                const params = new URLSearchParams({ store_id: els.store.value, q: query });
                const res = await fetch(`${root.dataset.vehiclesUrl}?${params}`);
                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message || 'Gagal memuat kendaraan');
                }

                if ((data.vehicles || []).length === 0) {
                    state.vehicleResults = [];
                    vehicleList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-light);">Tidak ada kendaraan ditemukan.</div>';
                    return;
                }

                state.vehicleResults = data.vehicles;

                vehicleList.innerHTML = data.vehicles.map((vehicle) => {
                    const svc = vehicle.last_service_at
                        ? `Servis terakhir ${formatDate(vehicle.last_service_at)}${vehicle.last_service_mileage ? ` · ${Number(vehicle.last_service_mileage).toLocaleString('id-ID')} km` : ''}`
                        : 'Belum ada riwayat servis';
                    return `
                    <div class="customer-item" onclick="selectVehicle(${vehicle.id}, '${escapeHtml(vehicle.plate_number)}', '${escapeHtml(vehicle.mileage || '')}', ${vehicle.customer?.id || 'null'}, '${escapeHtml(vehicle.customer?.name || '')}', '${escapeHtml(vehicle.customer?.phone || '')}')">
                        <h4>${escapeHtml(vehicle.plate_number)}${vehicle.name ? ` · ${escapeHtml(vehicle.name)}` : ''}</h4>
                        <p>${escapeHtml(vehicle.customer?.name || 'Tanpa customer')}${vehicle.customer?.phone ? ` · ${escapeHtml(vehicle.customer.phone)}` : ''}</p>
                        <p style="color:var(--brand); font-weight:600;">${svc}</p>
                    </div>
                `;
                }).join('');
            } catch (err) {
                vehicleList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--danger);">Gagal memuat kendaraan.</div>';
            }
        };

        els.vehiclePlateNumber.addEventListener('focus', () => {
            loadVehicles(els.vehiclePlateNumber.value.trim());
        });

        els.vehiclePlateNumber.addEventListener('click', () => {
            loadVehicles(els.vehiclePlateNumber.value.trim());
        });

        els.vehiclePlateNumber.addEventListener('input', (event) => {
            clearTimeout(vehiclesTimer);
            vehiclesTimer = setTimeout(() => {
                loadVehicles(event.target.value.trim());
            }, 300);
        });

        window.selectCustomer = (id, name, phone, plateNumber = '', mileage = '') => {
            els.customerId.value = id;
            els.customerName.value = name;
            els.customerPhone.value = phone;
            els.vehiclePlateNumber.value = plateNumber;
            els.vehicleMileage.value = mileage;
            customerSearch.value = name;
            state.customerDuplicate = false;
            customerList.style.display = 'none';
            vehicleList.style.display = 'none';
            renderOrder();
        };

        window.selectVehicleOwner = (id, name, phone) => {
            vehicleOwnerId.value = id;
            vehicleOwnerName.value = name;
            vehicleOwnerPhone.value = phone || '';
            vehicleOwnerList.style.display = 'none';
        };

        window.selectVehicle = (id, plateNumber, mileage, customerId, customerName, customerPhone) => {
            els.vehiclePlateNumber.value = plateNumber;
            els.vehicleMileage.value = mileage || '';

            if (customerId) {
                els.customerId.value = customerId;
                els.customerName.value = customerName;
                els.customerPhone.value = customerPhone;
                customerSearch.value = customerName;
                state.customerDuplicate = false;
            }

            const vehicle = state.vehicleResults.find((v) => String(v.id) === String(id));
            renderVehicleService(vehicle);

            vehicleList.style.display = 'none';
            customerList.style.display = 'none';
            renderOrder();
        };

        const renderVehicleService = (vehicle) => {
            const box = document.getElementById('vehicle-service-info');
            if (!box) return;

            if (!vehicle) {
                box.style.display = 'none';
                box.innerHTML = '';
                return;
            }

            const km = vehicle.last_service_mileage ? `${Number(vehicle.last_service_mileage).toLocaleString('id-ID')} km` : '-';
            const when = vehicle.last_service_at ? formatDate(vehicle.last_service_at) : '-';
            const summary = vehicle.last_service_summary || '';

            box.style.display = 'block';
            box.innerHTML = vehicle.last_service_at
                ? `<div class="svc-row"><span>Servis terakhir</span><strong>${when}</strong></div>
                   <div class="svc-row"><span>KM saat itu</span><strong>${km}</strong></div>
                   ${summary ? `<div class="svc-summary">${escapeHtml(summary)}</div>` : ''}`
                : `<div class="svc-empty">Belum ada riwayat servis untuk kendaraan ini.</div>`;
        };

        const validateCustomerDuplicate = async (showMessage = false) => {
            if (els.customerId.value) {
                state.customerDuplicate = false;
                return false;
            }

            const name = els.customerName.value.trim();
            const phone = els.customerPhone.value.trim();

            if (!name && !phone) {
                state.customerDuplicate = false;
                return false;
            }

            const params = new URLSearchParams({
                store_id: els.store.value,
                name,
                phone,
            });

            const response = await fetch(`${root.dataset.customerCheckUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) return false;

            const data = await response.json();
            state.customerDuplicate = Boolean(data.exists);

            if (state.customerDuplicate && showMessage) {
                showToast(data.message || 'Pelanggan sudah terdaftar', 'error');
            }

            return state.customerDuplicate;
        };

        const receiptHtml = (sale) => {
            if (sale.receipt_type === 'refund') {
                return refundReceiptHtml(sale);
            }

            const items = (sale.items || []).map((item) => `
                <div class="receipt-item">
                    <div class="receipt-item-name">${escapeHtml(item.name)}</div>
                    <div class="receipt-row">
                        <span>${item.quantity} x ${rupiah(item.unit_price)}</span>
                        <strong>${rupiah(item.line_total)}</strong>
                    </div>
                    ${Number(item.tax_amount || 0) > 0 || Number(item.service_fee_amount || 0) > 0 ? `
                        <div class="transaction-meta">Tax ${rupiah(item.tax_amount || 0)} · Service ${rupiah(item.service_fee_amount || 0)} / item</div>
                    ` : ''}
                </div>
            `).join('');
            const debtLine = sale.is_debt
                ? `<div class="receipt-line"><span>Hutang</span><strong>${rupiah(sale.debt_amount)}</strong></div>`
                : '';

            return `
                <div class="receipt-head">
                    <h3>${escapeHtml(sale.store_name || 'omsetaPOS')}</h3>
                    <div>${escapeHtml(sale.number)}</div>
                    <div>${escapeHtml(sale.paid_at || '')}</div>
                </div>
                <div class="receipt-line"><span>Kasir</span><strong>${escapeHtml(sale.cashier_name || '-')}</strong></div>
                <div class="receipt-line"><span>Pelanggan</span><strong>${escapeHtml(sale.customer_name || 'Pelanggan Umum')}</strong></div>
                ${sale.vehicle_plate_number ? `<div class="receipt-line"><span>Plat</span><strong>${escapeHtml(sale.vehicle_plate_number)}</strong></div>` : ''}
                ${sale.vehicle_mileage ? `<div class="receipt-line"><span>Kilometer</span><strong>${Number(sale.vehicle_mileage).toLocaleString('id-ID')} km</strong></div>` : ''}
                <div class="receipt-line"><span>Status</span><strong>${escapeHtml(sale.payment_status_label || 'Lunas')}</strong></div>
                <div class="receipt-items">${items}</div>
                <div class="receipt-line"><span>Subtotal</span><strong>${rupiah(sale.subtotal)}</strong></div>
                <div class="receipt-line"><span>Diskon</span><strong>- ${rupiah(sale.discount_total)}</strong></div>
                <div class="receipt-line"><span>Service fee</span><strong>${rupiah(sale.service_fee_total)}</strong></div>
                <div class="receipt-line"><span>Tax</span><strong>${rupiah(sale.tax_total)}</strong></div>
                <div class="receipt-line receipt-total"><span>Total</span><strong>${rupiah(sale.grand_total)}</strong></div>
                <div class="receipt-line"><span>Bayar</span><strong>${rupiah(sale.paid_amount)}</strong></div>
                <div class="receipt-line"><span>Kembali</span><strong>${rupiah(sale.change_amount)}</strong></div>
                ${debtLine}
                <div class="receipt-head" style="border-bottom:0;border-top:1px dashed #94a3b8;padding:10px 0 0;margin:10px 0 0;">
                    Terima kasih
                </div>
            `;
        };

        const refundReceiptHtml = (refund) => {
            const returnedItems = (refund.items || [])
                .filter((item) => item.direction === 'returned')
                .map((item) => `
                    <div class="receipt-item">
                        <div class="receipt-item-name">${escapeHtml(item.name)}</div>
                        <div class="receipt-row">
                            <span>${item.quantity} x ${rupiah(item.unit_price)}</span>
                            <strong>${rupiah(item.line_total)}</strong>
                        </div>
                    </div>
                `).join('');
            const replacementItems = (refund.items || [])
                .filter((item) => item.direction === 'replacement')
                .map((item) => `
                    <div class="receipt-item">
                        <div class="receipt-item-name">${escapeHtml(item.name)}</div>
                        <div class="receipt-row">
                            <span>${item.quantity} x ${rupiah(item.unit_price)}</span>
                            <strong>${rupiah(item.line_total)}</strong>
                        </div>
                    </div>
                `).join('');

            return `
                <div class="receipt-head">
                    <h3>${escapeHtml(refund.store_name || 'omsetaPOS')}</h3>
                    <div>${escapeHtml(refund.number)}</div>
                    <div>Refund dari ${escapeHtml(refund.sale_number || '-')}</div>
                    <div>${escapeHtml(refund.created_at || '')}</div>
                </div>
                <div class="receipt-line"><span>Diproses</span><strong>${escapeHtml(refund.handled_by_name || '-')}</strong></div>
                <div class="receipt-line"><span>Pelanggan</span><strong>${escapeHtml(refund.customer_name || 'Pelanggan Umum')}</strong></div>
                <div class="receipt-line"><span>Tipe</span><strong>${escapeHtml(refund.type === 'exchange' ? 'Ganti barang' : 'Full refund')}</strong></div>
                <div class="receipt-items">
                    <div class="receipt-item-name">Barang dikembalikan</div>
                    ${returnedItems || '<div class="transaction-meta">Tidak ada item</div>'}
                </div>
                ${replacementItems ? `
                    <div class="receipt-items">
                        <div class="receipt-item-name">Barang pengganti</div>
                        ${replacementItems}
                    </div>
                ` : ''}
                <div class="receipt-line"><span>Nilai retur</span><strong>${rupiah(refund.returned_total)}</strong></div>
                <div class="receipt-line"><span>Barang pengganti</span><strong>${rupiah(refund.replacement_total)}</strong></div>
                <div class="receipt-line receipt-total"><span>Uang kembali</span><strong>${rupiah(refund.refund_amount)}</strong></div>
                <div class="receipt-line"><span>Tambahan wajib</span><strong>${rupiah(refund.additional_payment_amount)}</strong></div>
                <div class="receipt-line"><span>Tambahan diterima</span><strong>${rupiah(refund.additional_paid_amount)}</strong></div>
                <div class="receipt-line"><span>Kembalian tambahan</span><strong>${rupiah(refund.change_amount)}</strong></div>
                ${refund.reason ? `<div class="receipt-line"><span>Catatan</span><strong>${escapeHtml(refund.reason)}</strong></div>` : ''}
                <div class="receipt-head" style="border-bottom:0;border-top:1px dashed #94a3b8;padding:10px 0 0;margin:10px 0 0;">
                    Refund selesai
                </div>
            `;
        };

        const showReceipt = (sale) => {
            lastReceiptSale = sale;
            receiptContent.innerHTML = receiptHtml(sale);
            markSalePaidButton.style.display = sale.receipt_type !== 'refund' && sale.payment_status === 'belum_lunas' ? 'block' : 'none';
            markSalePaidButton.disabled = false;
            receiptModal.classList.remove('hidden');
        };

        const printReceipt = () => {
            if (!lastReceiptSale) return;

            const printWindow = window.open('', '_blank', 'width=420,height=640');
            if (!printWindow) {
                showToast('Popup print diblokir browser', 'error');
                return;
            }

            printWindow.document.write(`
                <!doctype html>
                <html>
                <head>
                    <title>${escapeHtml(lastReceiptSale.number)}</title>
                    <style>
                        @page { size: 80mm auto; margin: 4mm; }
                        * { box-sizing: border-box; }
                        body { margin: 0; color: #111; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
                        .receipt { width: 72mm; margin: 0 auto; font-size: 11px; }
                        .receipt-head { text-align: center; border-bottom: 1px dashed #111; padding-bottom: 8px; margin-bottom: 8px; }
                        .receipt-head h3 { font-size: 15px; margin: 0 0 4px; }
                        .receipt-line, .receipt-row { display: flex; justify-content: space-between; gap: 8px; line-height: 1.45; }
                        .receipt-items { border-top: 1px dashed #111; border-bottom: 1px dashed #111; margin: 8px 0; padding: 6px 0; }
                        .receipt-item { margin-bottom: 6px; }
                        .receipt-item-name { font-weight: 700; line-height: 1.35; }
                        .receipt-total { font-weight: 800; font-size: 13px; }
                    </style>
                </head>
                <body>
                    <div class="receipt">${receiptHtml(lastReceiptSale)}</div>
                    <script>
                        window.addEventListener('load', () => {
                            window.print();
                            setTimeout(() => window.close(), 500);
                        });
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        };

        const transactionStatus = (sale) => {
            if (['refunded', 'partially_refunded'].includes(sale.status)) {
                return { label: 'refund', className: 'refund' };
            }

            if (sale.payment_status === 'belum_lunas') {
                return { label: 'belum lunas', className: 'debt' };
            }

            return { label: sale.payment_status_label || 'lunas', className: '' };
        };

        const renderTransactions = (transactions) => {
            state.transactions = transactions;

            if (transactions.length === 0) {
                transactionList.innerHTML = '<div style="text-align:center; padding:24px; color:var(--muted);">Riwayat transaksi tidak ditemukan.</div>';
                return;
            }

            transactionList.innerHTML = transactions.map((sale) => {
                const itemText = sale.items
                    .map((item) => `${escapeHtml(item.name)} x${item.quantity}`)
                    .join(', ');
                const debtText = sale.is_debt ? ` · Hutang ${rupiah(sale.debt_amount)}` : '';
                const vehicleText = sale.vehicle_plate_number ? ` · ${escapeHtml(sale.vehicle_plate_number)}` : '';
                const status = transactionStatus(sale);

                return `
                    <div class="transaction-item" data-sale-id="${sale.id}" role="button" tabindex="0" title="Klik untuk membuka detail nota">
                        <div class="transaction-top">
                            <div>
                                <div class="transaction-number">${escapeHtml(sale.number)}</div>
                                <div class="transaction-meta">${escapeHtml(sale.customer_name)}${vehicleText} · ${escapeHtml(sale.payment_method)}${debtText} · ${escapeHtml(sale.paid_at)}</div>
                                <div class="transaction-status ${status.className}">${status.label}</div>
                            </div>
                            <div class="transaction-total">${rupiah(sale.grand_total)}</div>
                        </div>
                        <div class="transaction-items">${itemText}</div>
                    </div>
                `;
            }).join('');
        };

        const loadTransactions = async () => {
            transactionList.innerHTML = '<div style="text-align:center; padding:24px; color:var(--muted);">Memuat...</div>';

            const params = new URLSearchParams({
                store_id: els.store.value,
                q: transactionSearch.value,
            });

            const response = await fetch(`${root.dataset.transactionsUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                transactionList.innerHTML = '<div style="text-align:center; padding:24px; color:var(--danger);">Gagal memuat riwayat transaksi.</div>';
                return;
            }

            const data = await response.json();
            renderTransactions(data.transactions || []);
        };

        const resetRefundForm = () => {
            state.refundSales = [];
            state.refundSelectedSale = null;
            state.refundProducts = [];
            state.refundReplacementCart.clear();
            state.refundReplacementDraft.clear();
            refundSearch.value = '';
            refundType.value = 'full';
            refundProductSearch.value = '';
            refundAdditionalPayment.value = '';
            refundEvidence.value = '';
            refundReason.value = '';
            refundSaleList.innerHTML = '';
            refundProductList.innerHTML = '';
            refundReplacementCart.innerHTML = '';
            refundReturnList.innerHTML = '';
            refundSelectedSale.innerHTML = 'Pilih transaksi dulu.';
            refundSummary.innerHTML = '<div class="summary-line"><span>Retur</span><strong>Rp 0</strong></div>';
            processRefundButton.disabled = true;
            refundReplacementSection.style.display = 'none';
        };

        const refundableItems = (sale) => (sale?.items || [])
            .filter((item) => Number(item.refundable_quantity || 0) > 0);

        const renderRefundSales = (sales) => {
            state.refundSales = sales;

            if (sales.length === 0) {
                refundSaleList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--muted);">Transaksi tidak ditemukan.</div>';
                return;
            }

            refundSaleList.innerHTML = sales.map((sale) => {
                const status = transactionStatus(sale);
                const active = state.refundSelectedSale && Number(state.refundSelectedSale.id) === Number(sale.id);
                const disabled = sale.payment_status === 'belum_lunas';

                return `
                    <div class="refund-sale ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}" data-refund-sale-id="${sale.id}" data-refund-disabled="${disabled ? '1' : '0'}">
                        <div class="transaction-top" style="margin-bottom:4px;">
                            <div>
                                <div class="transaction-number">${escapeHtml(sale.number)}</div>
                                <div class="transaction-meta">${escapeHtml(sale.customer_name)}${sale.vehicle_plate_number ? ` · ${escapeHtml(sale.vehicle_plate_number)}` : ''} · ${escapeHtml(sale.paid_at)}</div>
                                <div class="transaction-status ${status.className}">${status.label}</div>
                            </div>
                            <div class="transaction-total">${rupiah(sale.grand_total)}</div>
                        </div>
                        ${disabled ? '<div class="transaction-meta" style="color:#92400e;font-weight:700;">Lunasi transaksi sebelum refund.</div>' : ''}
                    </div>
                `;
            }).join('');
        };

        const loadRefundSales = async () => {
            refundSaleList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--muted);">Memuat...</div>';

            const params = new URLSearchParams({
                store_id: els.store.value,
                q: refundSearch.value,
            });

            const response = await fetch(`${root.dataset.transactionsUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                refundSaleList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--danger);">Gagal memuat transaksi.</div>';
                return;
            }

            const data = await response.json();
            renderRefundSales(data.transactions || []);
        };

        const selectedReturnRows = () => {
            const sale = state.refundSelectedSale;
            if (!sale) return [];

            if (refundType.value === 'full') {
                return refundableItems(sale).map((item) => ({
                    sale_item_id: item.id,
                    quantity: Number(item.refundable_quantity || 0),
                    unit_price: Number(item.unit_price || 0),
                }));
            }

            return Array.from(refundReturnList.querySelectorAll('[data-refund-return-id]'))
                .map((input) => {
                    const saleItem = sale.items.find((item) => Number(item.id) === Number(input.dataset.refundReturnId));

                    return {
                        sale_item_id: Number(input.dataset.refundReturnId),
                        quantity: Math.max(0, Number(input.value || 0)),
                        unit_price: Number(saleItem?.unit_price || 0),
                    };
                })
                .filter((item) => item.quantity > 0);
        };

        const refundTotals = () => {
            const returnedTotal = selectedReturnRows()
                .reduce((total, item) => total + (item.quantity * item.unit_price), 0);
            const replacementTotal = Array.from(state.refundReplacementCart.values())
                .reduce((total, item) => total + (item.quantity * item.price), 0);

            return {
                returnedTotal,
                replacementTotal,
                refundAmount: Math.max(0, returnedTotal - replacementTotal),
                additionalPayment: Math.max(0, replacementTotal - returnedTotal),
                additionalPaid: currencyValue(refundAdditionalPayment),
                additionalChange: Math.max(0, currencyValue(refundAdditionalPayment) - Math.max(0, replacementTotal - returnedTotal)),
            };
        };

        const renderRefundSummary = () => {
            const totals = refundTotals();
            const needsReplacement = refundType.value === 'exchange';
            const hasReplacement = state.refundReplacementCart.size > 0;
            const hasEvidence = refundEvidence.files.length > 0;
            const additionalPaid = currencyValue(refundAdditionalPayment);
            const additionalOk = additionalPaid >= totals.additionalPayment;
            const canProcess = Boolean(state.refundSelectedSale)
                && selectedReturnRows().length > 0
                && hasEvidence
                && (!needsReplacement || hasReplacement)
                && (!needsReplacement || additionalOk);

            refundSummary.innerHTML = `
                <div class="summary-line"><span>Nilai retur</span><strong>${rupiah(totals.returnedTotal)}</strong></div>
                <div class="summary-line"><span>Barang pengganti</span><strong>${rupiah(totals.replacementTotal)}</strong></div>
                <div class="summary-line"><span>Uang kembali</span><strong>${rupiah(totals.refundAmount + totals.additionalChange)}</strong></div>
                <div class="summary-line"><span>Tambahan wajib</span><strong>${rupiah(totals.additionalPayment)}</strong></div>
                <div class="summary-line"><span>Tambahan diterima</span><strong>${rupiah(totals.additionalPaid)}</strong></div>
            `;
            processRefundButton.disabled = !canProcess;
        };

        const renderRefundDetails = () => {
            const sale = state.refundSelectedSale;
            const isExchange = refundType.value === 'exchange';
            refundReplacementSection.style.display = isExchange ? 'flex' : 'none';

            if (!sale) {
                refundSelectedSale.innerHTML = 'Pilih transaksi dulu.';
                refundReturnList.innerHTML = '';
                renderRefundSummary();
                return;
            }

            const items = refundableItems(sale);
            const status = transactionStatus(sale);

            refundSelectedSale.innerHTML = `
                <div style="text-align:left;">
                    <div class="transaction-number">${escapeHtml(sale.number)}</div>
                    <div class="transaction-meta">${escapeHtml(sale.customer_name)} · ${escapeHtml(sale.paid_at)}</div>
                    <div class="transaction-status ${status.className}">${status.label}</div>
                </div>
            `;

            if (items.length === 0) {
                refundReturnList.innerHTML = '<div style="text-align:center; padding:16px; color:var(--muted);">Semua item transaksi ini sudah direfund.</div>';
                renderRefundSummary();
                return;
            }

            refundReturnList.innerHTML = items.map((item) => `
                <div class="refund-line">
                    <div>
                        <div class="transaction-number">${escapeHtml(item.name)}</div>
                        <div class="transaction-meta">Dibeli ${item.quantity} · Sisa refund ${item.refundable_quantity} · ${rupiah(item.unit_price)}</div>
                    </div>
                    <input type="number" min="0" max="${item.refundable_quantity}" value="${isExchange ? Math.min(1, Number(item.refundable_quantity)) : item.refundable_quantity}"
                        data-refund-return-id="${item.id}" ${isExchange ? '' : 'disabled'}>
                </div>
            `).join('');

            renderRefundSummary();
        };

        const loadRefundProducts = async () => {
            if (refundType.value !== 'exchange') return;

            const params = new URLSearchParams({
                store_id: els.store.value,
                q: refundProductSearch.value,
            });

            const response = await fetch(`${root.dataset.productsUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                refundProductList.innerHTML = '<div style="text-align:center; padding:16px; color:var(--danger);">Gagal memuat produk.</div>';
                return;
            }

            const data = await response.json();
            state.refundProducts = data.products || [];
            renderRefundProducts();
        };

        const cloneReplacementCart = () => new Map(
            Array.from(state.refundReplacementCart.entries()).map(([id, item]) => [id, { ...item }])
        );

        const openReplacementPicker = async () => {
            if (refundType.value !== 'exchange') return;

            state.refundReplacementDraft = cloneReplacementCart();
            refundProductSearch.value = '';
            refundProductModal.classList.remove('hidden');
            refundProductSearch.focus();
            await loadRefundProducts();
        };

        const renderRefundProducts = () => {
            if (state.refundProducts.length === 0) {
                refundProductList.innerHTML = '<div style="text-align:center; padding:32px; color:var(--muted); grid-column:1/-1;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;margin:0 auto 16px;color:var(--line);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><p style="font-weight:500;font-size:16px;">Produk tidak ditemukan</p></div>';
                return;
            }

            refundProductList.innerHTML = state.refundProducts.slice(0, 20).map((product) => {
                const selected = state.refundReplacementDraft.get(product.id);
                const quantity = selected?.quantity || 1;

                return `
                <label class="refund-product-card">
                    <input type="checkbox" data-refund-draft-check="${product.id}" ${selected ? 'checked' : ''}>
                    <div class="cart-thumb">
                        ${productImage(product)}
                    </div>
                    <div class="refund-product-card-content">
                        <div class="refund-product-card-title">${escapeHtml(product.name)}</div>
                        <div class="refund-product-card-meta">
                            <span>${escapeHtml(product.code || '-')}</span>
                            <span>&bull;</span>
                            <span style="color: ${product.product_type === 'service' || product.stock > 0 ? 'var(--text)' : 'var(--danger)'}">${product.product_type === 'service' ? 'Jasa' : `Stok ${product.stock}`}</span>
                            <span>&bull;</span>
                            <span class="refund-product-card-price">${rupiah(product.price)}</span>
                        </div>
                        <div class="refund-product-card-qty">
                            <span>Kuantitas:</span>
                            <input type="number" min="1" ${product.product_type === 'service' ? '' : `max="${product.stock}"`} value="${quantity}" data-refund-draft-qty="${product.id}"
                                class="input" ${selected ? '' : 'disabled'}>
                        </div>
                    </div>
                </label>
                `;
            }).join('');
        };

        const setRefundDraftProduct = (productId, checked) => {
            const product = state.refundProducts.find((item) => Number(item.id) === Number(productId))
                || state.refundReplacementCart.get(productId);
            if (!product) return;

            if (!checked) {
                state.refundReplacementDraft.delete(product.id);
                renderRefundProducts();
                return;
            }

            const current = state.refundReplacementDraft.get(product.id);
            state.refundReplacementDraft.set(product.id, {
                ...product,
                quantity: current?.quantity || 1,
            });
            renderRefundProducts();
        };

        const changeRefundDraftQuantity = (productId, quantity) => {
            const item = state.refundReplacementDraft.get(productId);
            if (!item) return;

            item.quantity = item.product_type === 'service'
                ? Math.max(1, Number(quantity || 1))
                : Math.min(item.stock, Math.max(1, Number(quantity || 1)));
            state.refundReplacementDraft.set(productId, item);
            renderRefundProducts();
        };

        const applyRefundProductSelection = () => {
            state.refundReplacementCart = new Map(
                Array.from(state.refundReplacementDraft.entries()).map(([id, item]) => [id, { ...item }])
            );
            refundProductModal.classList.add('hidden');
            renderRefundReplacementCart();
        };

        const renderRefundReplacementCart = () => {
            const items = Array.from(state.refundReplacementCart.values());

            if (items.length === 0) {
                refundReplacementCart.innerHTML = '<div style="text-align:center; padding:12px; color:var(--muted); border:1px dashed var(--line); border-radius:var(--radius-md);">Belum ada barang pengganti.</div>';
                renderRefundSummary();
                return;
            }

            refundReplacementCart.innerHTML = items.map((item) => `
                <div class="refund-line">
                    <div>
                        <div class="transaction-number">${escapeHtml(item.name)}</div>
                        <div class="transaction-meta">${rupiah(item.price)} · ${item.product_type === 'service' ? 'Jasa' : `Stok ${item.stock}`}</div>
                    </div>
                    <div style="display:flex; gap:6px; align-items:center;">
                        <button class="icon-btn" type="button" data-refund-replacement-dec="${item.id}">-</button>
                        <strong>${item.quantity}</strong>
                        <button class="icon-btn" type="button" data-refund-replacement-inc="${item.id}">+</button>
                    </div>
                </div>
            `).join('');
            renderRefundSummary();
        };

        const changeRefundReplacementQty = (productId, delta) => {
            const item = state.refundReplacementCart.get(productId);
            if (!item) return;

            const nextQuantity = item.quantity + delta;
            if (nextQuantity <= 0) {
                state.refundReplacementCart.delete(productId);
            } else if (item.product_type === 'service' || nextQuantity <= item.stock) {
                item.quantity = nextQuantity;
                state.refundReplacementCart.set(productId, item);
            }

            renderRefundReplacementCart();
        };

        const processRefund = async () => {
            const sale = state.refundSelectedSale;
            if (!sale) {
                showToast('Pilih transaksi yang ingin direfund', 'error');
                return;
            }

            if (refundEvidence.files.length === 0) {
                showToast('Upload minimal 1 foto bukti refund', 'error');
                return;
            }

            const totals = refundTotals();
            if (refundType.value === 'exchange' && currencyValue(refundAdditionalPayment) < totals.additionalPayment) {
                showToast('Tambahan pembayaran barang pengganti masih kurang', 'error');
                return;
            }

            const payload = new FormData();
            payload.append('store_id', els.store.value);
            payload.append('sale_id', sale.id);
            payload.append('type', refundType.value);
            payload.append('reason', refundReason.value);
            payload.append('additional_payment_amount', currencyValue(refundAdditionalPayment));

            Array.from(refundEvidence.files).forEach((file) => {
                payload.append('evidence_photos[]', file);
            });

            selectedReturnRows().forEach((item, index) => {
                payload.append(`returned_items[${index}][sale_item_id]`, item.sale_item_id);
                payload.append(`returned_items[${index}][quantity]`, item.quantity);
            });

            Array.from(state.refundReplacementCart.values()).forEach((item, index) => {
                payload.append(`replacement_items[${index}][product_id]`, item.id);
                payload.append(`replacement_items[${index}][quantity]`, item.quantity);
            });

            processRefundButton.disabled = true;

            try {
                const response = await fetch(root.dataset.refundsUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf,
                    },
                    body: payload,
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Refund gagal diproses');
                }

                showToast(`Refund ${data.refund.number} selesai diproses`, 'success');
                refundModal.classList.add('hidden');
                showReceipt(data.refund);
                resetRefundForm();
                await loadProducts();
                if (!transactionModal.classList.contains('hidden')) {
                    loadTransactions();
                }
            } catch (error) {
                showToast(error.message, 'error');
                renderRefundSummary();
            }
        };

        const markTransactionPaid = async () => {
            if (!lastReceiptSale || lastReceiptSale.receipt_type === 'refund') return;
            if (lastReceiptSale.payment_status !== 'belum_lunas') return;

            markSalePaidButton.disabled = true;

            try {
                const url = root.dataset.markPaidUrl.replace('/0/', `/${lastReceiptSale.id}/`);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf,
                    },
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Gagal set transaksi lunas');
                }

                lastReceiptSale = data.sale;
                state.transactions = state.transactions.map((sale) => Number(sale.id) === Number(data.sale.id) ? data.sale : sale);
                receiptContent.innerHTML = receiptHtml(data.sale);
                markSalePaidButton.style.display = 'none';
                showToast(`Transaksi ${data.sale.number} sudah lunas`, 'success');

                if (!transactionModal.classList.contains('hidden')) {
                    renderTransactions(state.transactions);
                }
            } catch (error) {
                showToast(error.message, 'error');
                markSalePaidButton.disabled = false;
            }
        };

        const openTransactionReceipt = (target) => {
            const item = target.closest('[data-sale-id]');
            if (!item) return;

            const sale = state.transactions.find((row) => Number(row.id) === Number(item.dataset.saleId));
            if (!sale) return;

            transactionModal.classList.add('hidden');
            showReceipt(sale);
        };

        document.getElementById('btn-open-transactions').addEventListener('click', () => {
            transactionModal.classList.remove('hidden');
            transactionSearch.value = '';
            transactionSearch.focus();
            loadTransactions();
        });

        document.getElementById('btn-open-refund').addEventListener('click', () => {
            resetRefundForm();
            refundModal.classList.remove('hidden');
            refundSearch.focus();
            loadRefundSales();
        });

        document.getElementById('close-transaction-modal').addEventListener('click', () => {
            transactionModal.classList.add('hidden');
        });

        document.getElementById('close-refund-modal').addEventListener('click', () => {
            refundModal.classList.add('hidden');
        });

        document.getElementById('close-refund-product-modal').addEventListener('click', () => {
            refundProductModal.classList.add('hidden');
        });

        document.getElementById('cancel-refund-products').addEventListener('click', () => {
            refundProductModal.classList.add('hidden');
        });

        document.getElementById('apply-refund-products').addEventListener('click', applyRefundProductSelection);

        transactionSearch.addEventListener('input', debounce(loadTransactions, 300));
        refundSearch.addEventListener('input', debounce(loadRefundSales, 300));
        refundType.addEventListener('change', () => {
            state.refundReplacementCart.clear();
            state.refundReplacementDraft.clear();
            refundAdditionalPayment.value = '';
            renderRefundDetails();
            renderRefundReplacementCart();
        });
        refundReturnList.addEventListener('input', renderRefundSummary);
        refundEvidence.addEventListener('change', renderRefundSummary);
        bindCurrencyInput(refundAdditionalPayment, renderRefundSummary);
        refundProductSearch.addEventListener('input', debounce(loadRefundProducts, 300));
        openRefundProducts.addEventListener('click', openReplacementPicker);
        refundSaleList.addEventListener('click', (event) => {
            const item = event.target.closest('[data-refund-sale-id]');
            if (!item) return;
            if (item.dataset.refundDisabled === '1') {
                showToast('Transaksi belum lunas tidak bisa direfund', 'error');
                return;
            }

            state.refundSelectedSale = state.refundSales.find((sale) => Number(sale.id) === Number(item.dataset.refundSaleId));
            state.refundReplacementCart.clear();
            state.refundReplacementDraft.clear();
            refundAdditionalPayment.value = '';
            renderRefundSales(state.refundSales);
            renderRefundDetails();
            renderRefundReplacementCart();
        });
        refundProductList.addEventListener('change', (event) => {
            const check = event.target.closest('[data-refund-draft-check]');
            const qty = event.target.closest('[data-refund-draft-qty]');

            if (check) setRefundDraftProduct(Number(check.dataset.refundDraftCheck), check.checked);
            if (qty) changeRefundDraftQuantity(Number(qty.dataset.refundDraftQty), qty.value);
        });
        refundProductList.addEventListener('input', (event) => {
            const qty = event.target.closest('[data-refund-draft-qty]');
            if (qty) changeRefundDraftQuantity(Number(qty.dataset.refundDraftQty), qty.value);
        });
        refundReplacementCart.addEventListener('click', (event) => {
            const inc = event.target.closest('[data-refund-replacement-inc]');
            const dec = event.target.closest('[data-refund-replacement-dec]');

            if (inc) changeRefundReplacementQty(Number(inc.dataset.refundReplacementInc), 1);
            if (dec) changeRefundReplacementQty(Number(dec.dataset.refundReplacementDec), -1);
        });
        processRefundButton.addEventListener('click', processRefund);
        transactionList.addEventListener('click', (event) => {
            openTransactionReceipt(event.target);
        });
        transactionList.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            openTransactionReceipt(event.target);
        });
        document.getElementById('close-receipt-modal').addEventListener('click', () => {
            receiptModal.classList.add('hidden');
        });
        document.getElementById('finish-receipt').addEventListener('click', () => {
            receiptModal.classList.add('hidden');
        });
        document.getElementById('print-receipt').addEventListener('click', printReceipt);
        markSalePaidButton.addEventListener('click', markTransactionPaid);

        // --- Pintasan keyboard (gaya kasir supermarket) ---
        const allModals = () => Array.from(document.querySelectorAll('[id$="-modal"]'));
        const openModals = () => allModals().filter((m) => !m.classList.contains('hidden'));
        const closeTopModal = () => {
            const open = openModals();
            if (open.length === 0) return false;
            open[open.length - 1].classList.add('hidden');
            return true;
        };

        document.addEventListener('keydown', (event) => {
            const key = event.key;

            if (key === 'Escape') {
                if (closeTopModal()) { event.preventDefault(); return; }
                // tutup dropdown saran
                ['customer-list', 'vehicle-list', 'vehicle-owner-list'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) el.style.display = 'none';
                });
                if (document.activeElement === els.search && els.search.value) {
                    els.search.value = '';
                    els.search.dispatchEvent(new Event('input'));
                }
                return;
            }

            // Pintasan fungsi hanya saat tidak ada modal terbuka
            if (openModals().length > 0) return;

            switch (key) {
                case 'F2': // fokus cari produk
                    event.preventDefault();
                    els.search.focus();
                    els.search.select();
                    break;
                case 'F3': // fokus pelanggan
                    event.preventDefault();
                    document.getElementById('customer-search')?.focus();
                    break;
                case 'F4': // fokus nominal bayar
                    event.preventDefault();
                    els.paidAmount?.focus();
                    els.paidAmount?.select();
                    break;
                case 'F9': // proses pembayaran
                    event.preventDefault();
                    if (!els.checkout.disabled) els.checkout.click();
                    break;
                case 'F6': // riwayat transaksi
                    event.preventDefault();
                    document.getElementById('btn-open-transactions')?.click();
                    break;
                case 'F7': // refund
                    event.preventDefault();
                    document.getElementById('btn-open-refund')?.click();
                    break;
                case 'Delete': // kosongkan keranjang (Ctrl+Delete)
                    if (event.ctrlKey) {
                        event.preventDefault();
                        document.getElementById('reset')?.click();
                    }
                    break;
            }
        });

        loadPricing().catch(() => { });
        loadProducts();
        loadEmployees().then(renderOrder);
        renderOrder();
