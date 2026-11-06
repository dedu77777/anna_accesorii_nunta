(function () {
    'use strict';

    const toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.hidden = true;
    toast.innerHTML = '<span class="cart-toast-icon" aria-hidden="true"></span><div class="cart-toast-copy"><strong></strong><p></p></div><button type="button" class="cart-toast-close" aria-label="Închide notificarea">×</button>';
    document.body.appendChild(toast);

    let toastTimer;
    const toastTitle = toast.querySelector('strong');
    const toastDescription = toast.querySelector('p');
    const toastIcon = toast.querySelector('.cart-toast-icon');
    const dismissToast = () => {
        clearTimeout(toastTimer);
        toast.hidden = true;
    };
    toast.querySelector('button').addEventListener('click', dismissToast);

    function showToast(type, title, description) {
        clearTimeout(toastTimer);
        toast.hidden = false;
        toast.classList.toggle('is-error', type === 'error');
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toastTitle.textContent = title;
        toastDescription.textContent = description;
        toastIcon.textContent = type === 'error' ? '!' : '✓';
        toastTimer = window.setTimeout(dismissToast, 4000);
    }

    function updateBadge(count) {
        const link = document.querySelector('.anna-cart-icon');
        if (!link || !Number.isInteger(count) || count < 0) return;
        let badge = link.querySelector('.anna-cart-count');
        if (count === 0) {
            badge?.remove();
        } else {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'anna-cart-count';
                link.appendChild(badge);
            }
            badge.textContent = String(count);
        }
        link.setAttribute('aria-label', `Coșul meu, ${count} produse`);
    }

    async function requestJson(url, options) {
        let response;
        try {
            response = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                ...options,
            });
        } catch (_) {
            throw new Error('Conexiunea a eșuat. Încearcă din nou.');
        }
        let data;
        try {
            data = await response.json();
        } catch (_) {
            throw new Error('Răspunsul serverului nu a putut fi citit. Încearcă din nou.');
        }
        if (response.status >= 500) {
            throw new Error('A apărut o eroare. Încearcă din nou.');
        }
        if (!response.ok || !data || data.success !== true) {
            throw new Error(typeof data?.message === 'string' ? data.message : 'Operațiunea nu a reușit. Încearcă din nou.');
        }
        return data;
    }

    function normalizedQuantity(input) {
        const value = Number(input.value);
        const quantity = Number.isInteger(value) ? Math.min(10, Math.max(1, value)) : 1;
        input.value = String(quantity);
        return quantity;
    }

    document.querySelectorAll('.home-cart-form, .products-cart-form, #product-detail-cart-form').forEach((form) => {
        const input = form.querySelector('input[name="quantity"]');
        const minus = form.querySelector('[aria-label="Scade cantitatea"]');
        const plus = form.querySelector('[aria-label="Crește cantitatea"]');
        const submit = form.querySelector('[type="submit"]');
        if (!input || !submit) return;
        let pending = false;
        const sync = () => {
            const quantity = normalizedQuantity(input);
            if (minus) minus.disabled = pending || quantity <= 1;
            if (plus) plus.disabled = pending || quantity >= 10;
        };
        minus?.addEventListener('click', () => { input.value = String(normalizedQuantity(input) - 1); sync(); });
        plus?.addEventListener('click', () => { input.value = String(normalizedQuantity(input) + 1); sync(); });
        input.addEventListener('change', sync);
        input.addEventListener('input', () => {
            if (input.value !== '') sync();
        });
        sync();
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (pending) return;
            const quantity = normalizedQuantity(input);
            pending = true;
            submit.disabled = true;
            sync();
            try {
                const data = await requestJson(form.action, { method: 'POST', body: new FormData(form) });
                if (!Number.isInteger(data.cartCount) || data.cartCount < 0) {
                    window.location.reload();
                    return;
                }
                updateBadge(Number(data.cartCount));
                const title = form.dataset.productTitle || 'Produsul';
                showToast('success', 'Produs adăugat în coș', `${title} × ${quantity}`);
            } catch (error) {
                showToast('error', 'Nu s-a putut adăuga produsul', error.message);
            } finally {
                pending = false;
                submit.disabled = false;
                sync();
            }
        });
    });

    const cartPage = document.querySelector('.cart-page');
    if (!cartPage) return;
    const layout = cartPage.querySelector('.cart-layout');
    const empty = cartPage.querySelector('.cart-empty');
    const countFields = cartPage.querySelectorAll('[data-cart-count]');
    const totalFields = cartPage.querySelectorAll('[data-cart-total]');
    const formatter = new Intl.NumberFormat('ro-MD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const money = (value) => `${formatter.format(value)} MDL`;
    const csrfParam = cartPage.dataset.csrfParam;
    const csrfToken = cartPage.dataset.csrfToken;

    function updateSummary(data) {
        if (!Number.isInteger(data.cartCount) || data.cartCount < 0 || typeof data.total !== 'number' || !Number.isFinite(data.total)) {
            window.location.reload();
            return false;
        }
        const count = Number(data.cartCount);
        const total = Number(data.total);
        countFields.forEach((field) => { field.textContent = String(count); });
        cartPage.querySelectorAll('[data-cart-count-label]').forEach((field) => { field.textContent = count === 1 ? 'produs' : 'produse'; });
        totalFields.forEach((field) => { field.textContent = money(total); });
        updateBadge(count);
        if (count === 0 && layout && empty) {
            layout.hidden = true;
            empty.hidden = false;
            empty.querySelector('a')?.focus();
        }
        return true;
    }

    function bodyFor(itemId, quantity) {
        const body = new URLSearchParams();
        body.set('cart_item_id', itemId);
        if (quantity !== undefined) body.set('quantity', String(quantity));
        body.set(csrfParam, csrfToken);
        return body;
    }

    cartPage.querySelectorAll('.cart-item').forEach((item) => {
        const itemId = item.dataset.cartItemId;
        const quantity = item.querySelector('[data-item-quantity]');
        const minus = item.querySelector('[data-quantity-minus]');
        const plus = item.querySelector('[data-quantity-plus]');
        const remove = item.querySelector('[data-remove-item]');
        const subtotal = item.querySelector('[data-item-subtotal]');
        let pending = false;
        const sync = () => {
            const value = Number(quantity.textContent);
            minus.disabled = pending || value <= 1;
            plus.disabled = pending || value >= 10;
            remove.disabled = pending;
            item.setAttribute('aria-busy', String(pending));
        };
        sync();

        async function changeQuantity(delta) {
            if (pending) return;
            const next = Number(quantity.textContent) + delta;
            if (next < 1 || next > 10) return;
            pending = true;
            sync();
            try {
                const data = await requestJson(cartPage.dataset.updateUrl, {
                    method: 'PUT',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: bodyFor(itemId, next),
                });
                if (!Number.isInteger(data.quantity) || typeof data.subtotal !== 'number' || !Number.isFinite(data.subtotal)) {
                    window.location.reload();
                    return;
                }
                quantity.textContent = String(data.quantity);
                subtotal.textContent = money(Number(data.subtotal));
                updateSummary(data);
            } catch (error) {
                showToast('error', 'Coșul nu a putut fi actualizat', error.message);
            } finally {
                pending = false;
                sync();
            }
        }
        minus.addEventListener('click', () => changeQuantity(-1));
        plus.addEventListener('click', () => changeQuantity(1));
        remove.addEventListener('click', async () => {
            if (pending) return;
            pending = true;
            sync();
            try {
                const data = await requestJson(cartPage.dataset.deleteUrl, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: bodyFor(itemId),
                });
                if (!Number.isInteger(data.cartCount) || data.cartCount < 0 || typeof data.total !== 'number' || !Number.isFinite(data.total)) {
                    window.location.reload();
                    return;
                }
                const productName = item.querySelector('.cart-item-top h3')?.textContent?.trim() || 'Produsul';
                item.remove();
                updateSummary(data);
                showToast('success', 'Produs eliminat din coș', `${productName} a fost șters din coș.`);
            } catch (error) {
                pending = false;
                sync();
                showToast('error', 'Produsul nu a putut fi eliminat', error.message);
            }
        });
    });
}());
