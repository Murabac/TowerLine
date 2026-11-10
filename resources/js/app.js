import Alpine from 'alpinejs';

window.Alpine = Alpine;

const prefetched = new Set();
let prefetchTimer = 0;

function pageContent() {
    return document.querySelector('[data-page-content]');
}

function sameSiteDocumentUrl(link) {
    if (! link || link.hasAttribute('data-no-loading') || link.hasAttribute('download')) {
        return null;
    }

    if (link.target && link.target !== '_self') {
        return null;
    }

    const raw = link.getAttribute('href') || '';

    if (raw === '' || raw.startsWith('#') || raw.startsWith('javascript:') || raw.startsWith('mailto:') || raw.startsWith('tel:')) {
        return null;
    }

    let url;

    try {
        url = new URL(link.href, window.location.href);
    } catch {
        return null;
    }

    if (url.origin !== window.location.origin || url.pathname.endsWith('/logout')) {
        return null;
    }

    if (url.pathname === window.location.pathname && url.search === window.location.search) {
        return null;
    }

    return url;
}

function showPageSkeleton() {
    const main = pageContent();

    if (! main || main.querySelector('[data-page-skeleton]')) {
        return;
    }

    main.classList.add('relative');

    const cover = document.createElement('div');
    cover.setAttribute('data-page-skeleton', '');
    cover.setAttribute('aria-hidden', 'true');
    cover.className = 'absolute inset-0 z-20 overflow-auto bg-surface';
    cover.innerHTML = `
        <div class="p-4 lg:p-8 space-y-5">
            <div class="h-8 w-48 animate-pulse rounded-lg bg-gray-200"></div>
            <div class="h-3 w-72 max-w-full animate-pulse rounded bg-gray-200"></div>
            <div class="space-y-3 rounded-2xl border border-gray-200/90 bg-white p-5 shadow-[0_1px_2px_rgba(16,24,40,0.04),0_8px_24px_rgba(16,24,40,0.04)]">
                <div class="h-4 w-1/3 animate-pulse rounded bg-gray-200"></div>
                <div class="h-3 w-full animate-pulse rounded bg-gray-100"></div>
                <div class="h-3 w-5/6 animate-pulse rounded bg-gray-100"></div>
                <div class="h-3 w-2/3 animate-pulse rounded bg-gray-100"></div>
            </div>
            <div class="space-y-3 rounded-2xl border border-gray-200/90 bg-white p-5 shadow-[0_1px_2px_rgba(16,24,40,0.04),0_8px_24px_rgba(16,24,40,0.04)]">
                <div class="h-4 w-1/4 animate-pulse rounded bg-gray-200"></div>
                <div class="h-3 w-full animate-pulse rounded bg-gray-100"></div>
                <div class="h-3 w-4/5 animate-pulse rounded bg-gray-100"></div>
            </div>
            <div class="space-y-3 rounded-2xl border border-gray-200/90 bg-white p-5 shadow-[0_1px_2px_rgba(16,24,40,0.04),0_8px_24px_rgba(16,24,40,0.04)]">
                <div class="h-4 w-2/5 animate-pulse rounded bg-gray-200"></div>
                <div class="h-3 w-full animate-pulse rounded bg-gray-100"></div>
                <div class="h-3 w-3/4 animate-pulse rounded bg-gray-100"></div>
            </div>
        </div>
    `;

    main.appendChild(cover);
}

function isDocumentLink(link, event) {
    if (! pageContent() || event.defaultPrevented || event.button !== 0) {
        return false;
    }

    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return false;
    }

    return sameSiteDocumentUrl(link) !== null;
}

function prefetchLink(link) {
    if (! link?.closest('#sidebar, [data-page-content]')) {
        return;
    }

    const url = sameSiteDocumentUrl(link);

    if (! url || prefetched.has(url.href)) {
        return;
    }

    prefetched.add(url.href);

    const hint = document.createElement('link');
    hint.rel = 'prefetch';
    hint.as = 'document';
    hint.href = url.href;
    document.head.appendChild(hint);
}

function waitOnControl(form, submitter) {
    const control = submitter?.hasAttribute('data-wait-label')
        ? submitter
        : form.querySelector('[data-wait-label]');

    if (! control || control.dataset.waiting === '1') {
        return;
    }

    const label = control.getAttribute('data-wait-label');

    if (! label) {
        return;
    }

    control.dataset.waiting = '1';
    control.setAttribute('aria-busy', 'true');

    const ring = document.createElement('span');
    ring.className = 'mr-2 inline-block h-3.5 w-3.5 shrink-0 animate-spin rounded-full border-2 border-current border-t-transparent align-[-2px]';
    ring.setAttribute('aria-hidden', 'true');
    control.replaceChildren(ring, document.createTextNode(label));

    const carriesValue = control instanceof HTMLButtonElement && control.name !== '' && control.value !== '';

    if (control instanceof HTMLButtonElement && ! carriesValue) {
        queueMicrotask(() => {
            control.disabled = true;
        });
    }
}

document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

    if (isDocumentLink(link, event)) {
        showPageSkeleton();
    }
});

document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) {
        return;
    }

    const form = event.target;

    if (! (form instanceof HTMLFormElement)) {
        return;
    }

    if (form.hasAttribute('data-no-loading') || event.submitter?.hasAttribute('data-no-loading')) {
        return;
    }

    if (form.target && form.target !== '_self') {
        return;
    }

    waitOnControl(form, event.submitter);
    showPageSkeleton();
});

document.addEventListener('pointerover', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

    window.clearTimeout(prefetchTimer);

    if (! link) {
        return;
    }

    prefetchTimer = window.setTimeout(() => prefetchLink(link), 120);
});

document.addEventListener('pointerout', () => {
    window.clearTimeout(prefetchTimer);
});

document.addEventListener('focusin', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

    if (link) {
        prefetchLink(link);
    }
});

window.addEventListener('pageshow', () => {
    document.querySelector('[data-page-skeleton]')?.remove();
});

Alpine.start();
