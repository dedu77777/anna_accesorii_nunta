(function () {
    'use strict';

    const form = document.querySelector('.home-search-form[data-search-url]');
    if (!form) return;

    const input = form.querySelector('input[name="q"]');
    const list = form.querySelector('[role="listbox"]');
    const status = form.querySelector('[data-search-status]');
    let timer;
    let request;
    let loadedQuery = null;
    let activeIndex = -1;

    function close() {
        list.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    }

    function open() {
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function message(text) {
        list.replaceChildren();
        const paragraph = document.createElement('p');
        paragraph.className = 'home-search-suggestion-message';
        paragraph.textContent = text;
        list.append(paragraph);
        status.textContent = text;
        open();
    }

    function showProducts(products) {
        list.replaceChildren();
        activeIndex = -1;

        if (!products.length) {
            message('Nu am găsit produse.');
            return;
        }

        products.forEach(function (product, index) {
            const link = document.createElement('a');
            link.className = 'home-search-suggestion';
            link.href = product.url;
            link.id = 'home-search-result-' + index;
            link.setAttribute('role', 'option');
            link.setAttribute('aria-selected', 'false');

            if (product.imageUrl) {
                const image = document.createElement('img');
                image.src = product.imageUrl;
                image.alt = '';
                image.loading = 'lazy';
                link.append(image);
            } else {
                const imageSpace = document.createElement('span');
                imageSpace.className = 'home-search-suggestion-image';
                imageSpace.setAttribute('aria-hidden', 'true');
                link.append(imageSpace);
            }

            const title = document.createElement('span');
            title.textContent = product.title;
            link.append(title);
            list.append(link);
        });

        status.textContent = products.length + ' produse găsite.';
        open();
    }

    function search() {
        const query = input.value.trim();
        clearTimeout(timer);
        if (request) request.abort();

        if (query.length < 2) {
            loadedQuery = null;
            close();
            return;
        }

        if (query === loadedQuery) {
            if (list.childElementCount) open();
            return;
        }

        timer = setTimeout(function () {
            const currentQuery = query;
            request = new AbortController();
            message('Se caută…');

            const url = new URL(form.dataset.searchUrl, window.location.href);
            url.searchParams.set('q', currentQuery);

            fetch(url, { signal: request.signal, headers: { Accept: 'application/json' } })
                .then(function (response) {
                    if (!response.ok) throw new Error('Search failed');
                    return response.json();
                })
                .then(function (data) {
                    if (input.value.trim() !== currentQuery) return;
                    loadedQuery = currentQuery;
                    showProducts(data.products);
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError' && input.value.trim() === currentQuery) {
                        message('Căutarea nu este disponibilă momentan.');
                    }
                });
        }, 280);
    }

    input.addEventListener('input', search);
    input.addEventListener('focus', function () {
        if (input.value.trim() === loadedQuery && list.childElementCount) open();
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            close();
            return;
        }

        const options = list.querySelectorAll('[role="option"]');
        if (list.hidden || !options.length) return;

        if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            window.location.assign(options[activeIndex].href);
            return;
        }

        if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;

        event.preventDefault();
        if (activeIndex >= 0) options[activeIndex].setAttribute('aria-selected', 'false');
        activeIndex = event.key === 'ArrowDown'
            ? (activeIndex + 1) % options.length
            : (activeIndex - 1 + options.length) % options.length;
        options[activeIndex].setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', options[activeIndex].id);
        options[activeIndex].scrollIntoView({ block: 'nearest' });
    });

    document.addEventListener('pointerdown', function (event) {
        if (!form.contains(event.target)) close();
    });
}());
