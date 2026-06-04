import './bootstrap';

import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Alpine = Alpine;
window.Pusher = Pusher;
window.Swal = Swal;

const themeStorageKey = 'emtq-theme';

function getPreferredTheme() {
    const storedTheme = window.localStorage.getItem(themeStorageKey);

    if (storedTheme === 'light' || storedTheme === 'dark') {
        return storedTheme;
    }

    return 'dark';
}

function applyTheme(theme) {
    const resolvedTheme = theme === 'light' ? 'light' : 'dark';

    document.documentElement.dataset.theme = resolvedTheme;

    if (document.body) {
        document.body.dataset.theme = resolvedTheme;
    }

    document.documentElement.style.colorScheme = resolvedTheme === 'light' ? 'light' : 'dark';
}

function renderThemeToggle(theme) {
    const isLight = theme === 'light';
    const label = isLight ? 'Beralih ke tema gelap' : 'Beralih ke tema terang';
    const icon = isLight
        ? '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2.5M12 19.5V22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M2 12h2.5M19.5 12H22M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 14.8A8.5 8.5 0 1 1 9.2 4a7 7 0 1 0 10.8 10.8Z"/></svg>';

    return `
        <span class="sr-only">${label}</span>
        <span class="theme-toggle__icon" aria-hidden="true">${icon}</span>
    `;
}

function ensureLiveNotificationsHost() {
    if (document.getElementById('mtq-live-notifications') || !document.body) {
        return;
    }

    const host = document.createElement('div');
    host.id = 'mtq-live-notifications';
    host.setAttribute('x-data', '');
    host.className = 'pointer-events-none fixed right-4 top-4 z-[80] flex w-full max-w-sm flex-col gap-3';
    host.innerHTML = `
        <template x-for="notification in $store.ui.notifications" :key="notification.id">
            <div
                class="pointer-events-auto rounded-[1.5rem] border px-4 py-4 shadow-[0_20px_45px_-28px_rgba(14,165,233,0.55)] backdrop-blur"
                x-bind:class="{
                    'border-cyan-400/25 bg-slate-900/92 text-slate-100': notification.tone === 'info' || notification.tone === 'score',
                    'border-emerald-400/25 bg-emerald-400/10 text-emerald-50': notification.tone === 'success',
                    'border-amber-400/25 bg-amber-400/10 text-amber-50': notification.tone === 'warning'
                }"
            >
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-2.5 w-2.5 rounded-full bg-current opacity-80"></div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold" x-text="notification.title"></p>
                        <p class="mt-1 text-sm leading-6 opacity-90" x-text="notification.message"></p>
                    </div>
                </div>
            </div>
        </template>
    `;

    document.body.appendChild(host);
}

const initialTheme = getPreferredTheme();
applyTheme(initialTheme);

const swalTheme = {
    background: '#0f172a',
    color: '#e2e8f0',
    confirmButtonColor: '#06b6d4',
    cancelButtonColor: '#475569',
};

const galleryPaginationScrollKey = 'emtq-gallery-pagination-scroll';

let loadingProgressTimer = null;

function setLoadingOverlayProgress(value) {
    const overlay = ensureLoadingOverlay();
    const percentNode = overlay.querySelector('[data-loading-percent]');
    const fillNode = overlay.querySelector('[data-loading-fill]');
    const progress = Math.max(0, Math.min(100, Number(value) || 0));

    if (percentNode) {
        percentNode.textContent = `${progress}%`;
    }

    if (fillNode) {
        fillNode.style.width = `${progress}%`;
    }
}

function ensureLoadingOverlay() {
    let overlay = document.getElementById('mtq-submit-loading-overlay');

    if (overlay) {
        return overlay;
    }

    overlay = document.createElement('div');
    overlay.id = 'mtq-submit-loading-overlay';
    overlay.className = 'mtq-submit-overlay';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML = `
        <div class="mtq-submit-overlay__panel" role="status" aria-live="polite" aria-label="Sedang memproses data">
            <div class="mtq-submit-overlay__spinner" aria-hidden="true"></div>
            <div class="mtq-submit-overlay__copy">
                <p class="mtq-submit-overlay__title" data-loading-title>Menyimpan data</p>
                <p class="mtq-submit-overlay__text" data-loading-text>Mohon tunggu, data sedang diproses.</p>
                <div class="mtq-submit-overlay__progress">
                    <div class="mtq-submit-overlay__progress-row">
                        <span class="mtq-submit-overlay__progress-label">Progres</span>
                        <span class="mtq-submit-overlay__progress-percent" data-loading-percent>0%</span>
                    </div>
                    <div class="mtq-submit-overlay__progress-track" aria-hidden="true">
                        <div class="mtq-submit-overlay__progress-fill" data-loading-fill></div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    return overlay;
}

function setLoadingOverlayTitle(value) {
    const overlay = ensureLoadingOverlay();
    const titleNode = overlay.querySelector('[data-loading-title]');

    if (titleNode) {
        titleNode.textContent = value;
    }
}

function setLoadingOverlayText(value) {
    const overlay = ensureLoadingOverlay();
    const textNode = overlay.querySelector('[data-loading-text]');

    if (textNode) {
        textNode.textContent = value;
    }
}

function showLoadingOverlay(message, options = {}) {
    const overlay = ensureLoadingOverlay();
    const isUpload = Boolean(message && String(message).toLowerCase().includes('unggah'));
    const autoProgress = options.autoProgress !== false;

    if (loadingProgressTimer) {
        window.clearInterval(loadingProgressTimer);
        loadingProgressTimer = null;
    }

    setLoadingOverlayTitle(isUpload ? 'Mengunggah berkas' : 'Menyimpan data');
    setLoadingOverlayText(message || (isUpload ? 'Mohon tunggu, berkas sedang diunggah.' : 'Mohon tunggu, data sedang diproses.'));

    overlay.classList.add('is-visible');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('mtq-loading-active');

    setLoadingOverlayProgress(0);

    if (!autoProgress) {
        return;
    }

    let progress = 0;
    loadingProgressTimer = window.setInterval(() => {
        progress = Math.min(progress + (progress < 30 ? 8 : progress < 70 ? 4 : 1), 90);
        setLoadingOverlayProgress(progress);
        if (progress >= 90 && loadingProgressTimer) {
            window.clearInterval(loadingProgressTimer);
            loadingProgressTimer = null;
        }
    }, isUpload ? 140 : 120);
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('mtq-submit-loading-overlay');

    if (loadingProgressTimer) {
        window.clearInterval(loadingProgressTimer);
        loadingProgressTimer = null;
    }

    if (!overlay) {
        return;
    }

    setLoadingOverlayProgress(0);

    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('mtq-loading-active');
}

function getSubmitButtons(form) {
    return Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
}

function getButtonLabel(button) {
    if (button instanceof HTMLInputElement) {
        return button.dataset.loadingLabel || button.value || 'Memproses';
    }

    const label = (button.dataset.loadingLabel || button.textContent || '').replace(/\s+/g, ' ').trim();
    return label || 'Memproses';
}

function setSubmitButtonLoading(button, label) {
    if (!(button instanceof HTMLElement)) {
        return;
    }

    if (button.dataset.loadingActive === 'true') {
        return;
    }

    if (button instanceof HTMLInputElement) {
        button.dataset.loadingOriginalValue = button.value;
        button.value = label;
    } else {
        button.dataset.loadingOriginalHtml = button.innerHTML;
        button.innerHTML = `
            <span class="mtq-submit-button-spinner" aria-hidden="true"></span>
            <span>${escapeHtml(label)}</span>
        `;
    }

    button.dataset.loadingActive = 'true';
    button.dataset.loadingDisabled = button.disabled ? 'true' : 'false';
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
}

function clearSubmitButtonLoading(button) {
    if (!(button instanceof HTMLElement) || button.dataset.loadingActive !== 'true') {
        return;
    }

    if (button instanceof HTMLInputElement) {
        if (button.dataset.loadingOriginalValue !== undefined) {
            button.value = button.dataset.loadingOriginalValue;
        }
        delete button.dataset.loadingOriginalValue;
    } else if (button.dataset.loadingOriginalHtml !== undefined) {
        button.innerHTML = button.dataset.loadingOriginalHtml;
        delete button.dataset.loadingOriginalHtml;
    }

    button.dataset.loadingActive = 'false';
    const shouldStayDisabled = button.dataset.loadingDisabled === 'true';
    delete button.dataset.loadingDisabled;
    button.disabled = shouldStayDisabled;
    button.removeAttribute('aria-busy');
}

function resetSubmitButtonLoading(form) {
    getSubmitButtons(form).forEach((button) => clearSubmitButtonLoading(button));
}

function activateSubmitLoading(form, submitter, message, options = {}) {
    const buttons = getSubmitButtons(form);
    const activeButton = submitter && buttons.includes(submitter) ? submitter : buttons[0] || null;
    const label = activeButton ? getButtonLabel(activeButton) : (form.dataset.loadingButtonText || 'Memproses');
    const loadingLabel = form.dataset.loadingButtonText || `${label}...`;

    if (activeButton) {
        setSubmitButtonLoading(activeButton, loadingLabel);
    }

    buttons.forEach((button) => {
        if (button !== activeButton) {
            button.disabled = true;
        }
    });

    showLoadingOverlay(message || form.dataset.loadingText || (String(form.enctype || '').includes('multipart/form-data')
        ? 'Mohon tunggu, berkas sedang diunggah.'
        : 'Mohon tunggu, data sedang diproses.'), { autoProgress: options.autoProgress !== false });
}

function submitFormWithProgress(form, submitter, message) {
    const action = submitter?.formAction || form.action || window.location.href;
    const method = String(submitter?.formMethod || form.method || 'POST').toUpperCase();
    const formData = new FormData(form);
    const submitterName = submitter?.getAttribute?.('name');
    const submitterValue = submitter instanceof HTMLInputElement ? submitter.value : (submitter?.value ?? '');

    if (submitterName) {
        formData.append(submitterName, submitterValue);
    }

    activateSubmitLoading(form, submitter, message, { autoProgress: false });
    setLoadingOverlayTitle('Mengunggah berkas');
    setLoadingOverlayText(message || 'Mohon tunggu, berkas sedang diunggah.');

    const xhr = new XMLHttpRequest();
    xhr.open(method, action, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.responseType = 'text';

    xhr.upload.addEventListener('progress', (event) => {
        if (!event.lengthComputable) {
            return;
        }

        const progress = Math.min(95, Math.max(1, Math.round((event.loaded / event.total) * 95)));
        setLoadingOverlayProgress(progress);
    });

    xhr.upload.addEventListener('load', () => {
        setLoadingOverlayProgress(96);
        setLoadingOverlayTitle('Memproses file');
        setLoadingOverlayText('Upload selesai, file sedang diproses di server...');
    });

    xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
            let redirectUrl = xhr.responseURL || action;
            let successMessage = 'Data berhasil diproses.';

            try {
                const payload = JSON.parse(xhr.responseText || '{}');
                redirectUrl = payload.redirect_url || redirectUrl;
                successMessage = payload.message || successMessage;
            } catch {
                // Non-JSON success responses are usually followed redirects.
            }

            setLoadingOverlayProgress(100);
            setLoadingOverlayTitle('Selesai');
            setLoadingOverlayText(successMessage);
            window.location.href = redirectUrl;
            return;
        }

        handleSubmitError(xhr);
    });

    xhr.addEventListener('error', () => {
        handleSubmitError(xhr);
    });

    xhr.addEventListener('timeout', () => {
        handleSubmitError(xhr, 'Permintaan upload terlalu lama. Silakan coba lagi.');
    });

    xhr.addEventListener('abort', () => {
        hideLoadingOverlay();
        document.querySelectorAll('form').forEach((item) => resetSubmitButtonLoading(item));
    });

    xhr.send(formData);
}

function handleSubmitError(xhr, fallbackMessage = 'Koneksi ke server terputus. Silakan coba lagi.') {
    hideLoadingOverlay();
    document.querySelectorAll('form').forEach((item) => resetSubmitButtonLoading(item));
    window.dispatchEvent(new CustomEvent('mtq-submit-failed'));

    let message = fallbackMessage;

    try {
        const payload = JSON.parse(xhr?.responseText || '{}');
        if (payload?.message) {
            message = payload.message;
        } else if (payload?.errors && typeof payload.errors === 'object') {
            message = Object.values(payload.errors)
                .flat()
                .filter(Boolean)
                .join(' ');
        }
    } catch (error) {
        const text = String(xhr?.responseText || '').trim();
        if (text) {
            message = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220) || fallbackMessage;
        }
    }

    message = normalizeSubmitErrorMessage(message || fallbackMessage);

    Swal.fire({
        ...swalTheme,
        icon: 'error',
        title: 'Permohonan tidak dapat diproses',
        text: message || fallbackMessage,
    });
}

function normalizeSubmitErrorMessage(message) {
    const text = String(message || '').trim();

    if (!text) {
        return 'Permintaan Anda tidak dapat diproses saat ini. Silakan periksa kembali data yang diisi lalu coba lagi.';
    }

    const replacements = [
        [/^the nik has already been taken\.?$/i, 'NIK tersebut sudah terdaftar pada data peserta lain.'],
        [/^the email has already been taken\.?$/i, 'Alamat email tersebut sudah digunakan pada akun lain.'],
        [/^the .* has already been taken\.?$/i, 'Data yang dimasukkan sudah terdaftar pada sistem.'],
        [/^the .* field is required\.?$/i, 'Masih ada isian wajib yang belum lengkap.'],
        [/^koneksi ke server terputus\. silakan coba lagi\.?$/i, 'Koneksi ke server terputus. Silakan periksa jaringan Anda lalu coba lagi.'],
        [/^terjadi kesalahan saat menyimpan data\.?$/i, 'Data tidak dapat disimpan saat ini. Silakan periksa kembali isian Anda.'],
    ];

    for (const [pattern, replacement] of replacements) {
        if (pattern.test(text)) {
            return replacement;
        }
    }

    return text;
}

function showQueuedSweetAlerts() {
    const payloadElement = document.getElementById('mtq-swal-payload');

    if (!payloadElement) {
        return;
    }

    let payload = {};

    try {
        payload = JSON.parse(payloadElement.textContent || '{}');
    } catch (error) {
        payload = {};
    }

    const queue = [];

    if (payload.toast && typeof payload.toast === 'object') {
        const toast = payload.toast;
        if (window.Alpine?.store && Alpine.store('ui')) {
            Alpine.store('ui').pushNotification({
                tone: toast.tone ?? 'success',
                title: toast.title ?? 'Berhasil',
                message: toast.message ?? '',
            });
        }
    }

    if (payload.status) {
        queue.push({
            icon: 'success',
            title: 'Berhasil',
            text: payload.status,
            timer: 3200,
            timerProgressBar: true,
        });
    }

    if (payload.warning) {
        queue.push({
            icon: 'warning',
            title: 'Sinkronisasi Gagal',
            text: payload.warning,
        });
    }

    if (Array.isArray(payload.errors) && payload.errors.length > 0) {
        queue.push({
            icon: 'warning',
            title: 'Perlu Diperiksa',
            html: payload.errors.map((message) => `<p class="mtq-swal-line">${escapeHtml(message)}</p>`).join(''),
        });
    }

    queue.reduce((chain, options) => chain.then(() => Swal.fire({ ...swalTheme, ...options })), Promise.resolve());
}

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = String(value ?? '');
    return element.innerHTML;
}

function handleFileInputChange(input) {
    if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
        return;
    }

    const maxFiles = Number(input.dataset.maxFiles || 0);

    if (!maxFiles || !input.multiple || !input.files || input.files.length <= maxFiles) {
        return;
    }

    const message = input.dataset.maxFilesMessage || `Maksimal ${maxFiles} file.`;
    input.value = '';

    Swal.fire({
        ...swalTheme,
        icon: 'warning',
        title: 'File terlalu banyak',
        text: message,
    });
}

function getCurrentGalleryPageNumber() {
    const params = new URLSearchParams(window.location.search);
    const page = Number(params.get('gallery_page') || 1);

    return Number.isFinite(page) && page > 0 ? page : 1;
}

function getGalleryPaginationScrollKey(page = getCurrentGalleryPageNumber()) {
    return `${galleryPaginationScrollKey}:${page}`;
}

function storeGalleryPaginationScrollPosition() {
    const gallerySection = document.getElementById('galeri-mtq');

    if (!gallerySection) {
        return;
    }

    const scrollOffset = Math.max(0, -gallerySection.getBoundingClientRect().top);
    window.sessionStorage.setItem(getGalleryPaginationScrollKey(), String(scrollOffset));
}

function scrollToInstant(top) {
    const html = document.documentElement;
    const previousBehavior = html.style.scrollBehavior;

    html.style.scrollBehavior = 'auto';
    window.scrollTo({
        top: Math.max(0, top),
        behavior: 'auto',
    });

    window.requestAnimationFrame(() => {
        html.style.scrollBehavior = previousBehavior;
    });
}

function restoreGalleryPaginationScrollPosition() {
    const section = document.getElementById('galeri-mtq');

    if (!section) {
        return;
    }

    const savedOffset = Number(window.sessionStorage.getItem(getGalleryPaginationScrollKey()));
    const hasSavedOffset = Number.isFinite(savedOffset) && savedOffset >= 0;
    const sectionTop = section.getBoundingClientRect().top + window.scrollY;
    const targetTop = hasSavedOffset ? sectionTop + savedOffset : sectionTop - 16;

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            scrollToInstant(targetTop);
            window.sessionStorage.removeItem(getGalleryPaginationScrollKey());
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    showQueuedSweetAlerts();

    document.addEventListener('change', (event) => {
        const target = event.target;

        if (target instanceof HTMLInputElement && target.type === 'file') {
            handleFileInputChange(target);
        }
    });

    document.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const galleryLink = target.closest('[data-gallery-pagination] a');
        if (galleryLink) {
            storeGalleryPaginationScrollPosition();
        }

        const sidebarLink = target.closest('.sidebar-shell a[href]');
        if (!sidebarLink) {
            return;
        }

        const sidebar = sidebarLink.closest('.sidebar-shell');
        const main = sidebar?.closest('main');
        if (!(main instanceof HTMLElement) || !window.Alpine?.$data) {
            return;
        }

        try {
            const data = window.Alpine.$data(main);
            if (data && typeof data === 'object' && 'mobileNavOpen' in data) {
                data.mobileNavOpen = false;
            }
        } catch {
            // Ignore Alpine lookup failures and let normal navigation continue.
        }
    });

    if (window.location.search.includes('gallery_page=')) {
        if ('scrollRestoration' in window.history) {
            window.history.scrollRestoration = 'manual';
        }
        restoreGalleryPaginationScrollPosition();
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const method = String(form.method || 'GET').toUpperCase();
        if (method === 'GET') {
            return;
        }

        const target = submitter?.formTarget || form.target || '';
        const enctype = String(submitter?.formEnctype || form.enctype || '').toLowerCase();
        const isMultipart = enctype.includes('multipart/form-data');

        if (target && target !== '_self') {
            return;
        }

        if (form.hasAttribute('data-native-submit')) {
            activateSubmitLoading(form, submitter, form.dataset.loadingText);
            return;
        }

        if (form.hasAttribute('data-swal-confirm')) {
            if (form.dataset.swalSubmitting !== 'true') {
                event.preventDefault();

                Swal.fire({
                    ...swalTheme,
                    icon: form.dataset.swalIcon || 'warning',
                    title: form.dataset.swalTitle || 'Lanjutkan aksi?',
                    text: form.dataset.swalText || 'Pastikan data sudah benar sebelum melanjutkan.',
                    showCancelButton: true,
                    confirmButtonText: form.dataset.swalConfirm || 'Ya, lanjutkan',
                    cancelButtonText: form.dataset.swalCancel || 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.swalSubmitting = 'true';
                        if (submitter) {
                            form.requestSubmit(submitter);
                            return;
                        }

                        form.requestSubmit();
                    }
                });

                return;
            }
        }

        if (isMultipart) {
            event.preventDefault();
            submitFormWithProgress(form, submitter, form.dataset.loadingText);
            return;
        }

        activateSubmitLoading(form, submitter, form.dataset.loadingText);
    });

    window.addEventListener('pageshow', () => {
        hideLoadingOverlay();
        document.querySelectorAll('form').forEach((form) => {
            resetSubmitButtonLoading(form);
            delete form.dataset.swalSubmitting;
            delete form.dataset.swalLoading;
        });

        if (window.location.search.includes('gallery_page=')) {
            if ('scrollRestoration' in window.history) {
                window.history.scrollRestoration = 'manual';
            }
            restoreGalleryPaginationScrollPosition();
        }
    });

    window.addEventListener('popstate', () => {
        if (window.location.search.includes('gallery_page=')) {
            if ('scrollRestoration' in window.history) {
                window.history.scrollRestoration = 'manual';
            }
            restoreGalleryPaginationScrollPosition();
        }
    });

    window.addEventListener('beforeunload', () => {
        if (loadingProgressTimer) {
            window.clearInterval(loadingProgressTimer);
            loadingProgressTimer = null;
        }
    });
});

// Override with production values if not already set
if (!import.meta.env.VITE_REVERB_APP_KEY || import.meta.env.VITE_REVERB_APP_KEY === 'undefined') {
    import.meta.env.VITE_REVERB_APP_KEY = 'jfwgnk8dktj0wkxgxcfu';
    import.meta.env.VITE_REVERB_HOST = 'localhost';
    import.meta.env.VITE_REVERB_PORT = '8080';
    import.meta.env.VITE_REVERB_SCHEME = 'http';
    import.meta.env.VITE_REALTIME_ENABLED = 'true';
}

const realtimeEnabled = import.meta.env.VITE_REALTIME_ENABLED === 'true';
const reverbAppKey = realtimeEnabled ? import.meta.env.VITE_REVERB_APP_KEY : '';
let realtimeConnected = false;
let ongoingSchedulesFallbackTimer = null;
let ongoingSchedulesFallbackDelayTimer = null;

function uiStore() {
    try {
        return Alpine.store('ui');
    } catch {
        return null;
    }
}

function setRealtimeConnected(connected) {
    realtimeConnected = connected;

    const ui = uiStore();
    if (ui) {
        ui.liveConnected = connected;
    }

    if (connected) {
        stopOngoingSchedulesFallbackPolling();
        return;
    }

    runWhenReady(() => {
        startOngoingSchedulesFallbackPolling();
    });
}

if (reverbAppKey) {
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbAppKey,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? '127.0.0.1',
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    const channel = window.Echo.channel('mtq-live');

    channel.listen('.score.updated', (payload) => {
        window.dispatchEvent(new CustomEvent('mtq-score-updated', { detail: payload }));
    });

    channel.listen('.participant.verification-updated', (payload) => {
        window.dispatchEvent(new CustomEvent('mtq-participant-verification-updated', { detail: payload }));
    });

    channel.listen('.announcement.published', (payload) => {
        window.dispatchEvent(new CustomEvent('mtq-announcement-published', { detail: payload }));
    });

    channel.listen('.schedule.updated', (payload) => {
        window.dispatchEvent(new CustomEvent('mtq-schedule-updated', { detail: payload }));
    });

    channel.listen('.participant.selected', (payload) => {
        window.dispatchEvent(new CustomEvent('mtq-participant-selected', { detail: payload }));
    });

    const pusherConnection = window.Echo?.connector?.pusher?.connection;

    if (pusherConnection) {
        pusherConnection.bind('connected', () => setRealtimeConnected(true));
        pusherConnection.bind('disconnected', () => setRealtimeConnected(false));
        pusherConnection.bind('unavailable', () => setRealtimeConnected(false));
        pusherConnection.bind('failed', () => setRealtimeConnected(false));
        pusherConnection.bind('state_change', ({ current }) => {
            if (current === 'connected') {
                setRealtimeConnected(true);
                return;
            }

            if (['disconnected', 'unavailable', 'failed'].includes(current)) {
                setRealtimeConnected(false);
            }
        });
    }
}

Alpine.store('ui', {
    mobileMenuOpen: false,
    liveConnected: realtimeConnected,
    notifications: [],
    theme: initialTheme,
    pushNotification(notification) {
        const id = Date.now() + Math.random();
        this.notifications = [
            ...this.notifications,
            {
                id,
                tone: notification.tone ?? 'info',
                title: notification.title ?? 'Pembaruan',
                message: notification.message ?? '',
            },
        ];

        window.setTimeout(() => {
            this.notifications = this.notifications.filter((item) => item.id !== id);
        }, 5000);
    },
    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        window.localStorage.setItem(themeStorageKey, this.theme);
        applyTheme(this.theme);

        const button = document.getElementById('mtq-theme-toggle');
        if (button) {
            button.setAttribute('aria-label', this.theme === 'light' ? 'Beralih ke tema gelap' : 'Beralih ke tema terang');
            button.title = this.theme === 'light' ? 'Beralih ke tema gelap' : 'Beralih ke tema terang';
            button.innerHTML = renderThemeToggle(this.theme);
        }
    },
});

ensureLiveNotificationsHost();

window.addEventListener('mtq-score-updated', (event) => {
    const detail = event.detail ?? {};

    Alpine.store('ui').pushNotification({
        tone: 'score',
        title: 'Nilai Baru Masuk',
        message: `${detail.participant ?? 'Peserta'} memperoleh ${Number(detail.score ?? 0).toFixed(2)}${detail.judging_round ? ` pada babak ${detail.judging_round}` : ''}.`,
    });
});

window.addEventListener('mtq-participant-verification-updated', (event) => {
    const detail = event.detail ?? {};
    const statusLabelMap = {
        verified: 'Terverifikasi',
        rejected: 'Ditolak',
        submitted: 'Menunggu',
        draft: 'Draft',
    };

    Alpine.store('ui').pushNotification({
        tone: detail.verification_status === 'rejected' ? 'warning' : 'success',
        title: 'Status Berkas Diperbarui',
        message: `${detail.participant ?? 'Peserta'} sekarang berstatus ${statusLabelMap[detail.verification_status] ?? detail.verification_status ?? 'diperbarui'}.`,
    });
});

window.addEventListener('mtq-announcement-published', (event) => {
    const detail = event.detail ?? {};
    const audience = detail.audience ?? 'all';
    const currentRole = window.MTQ_USER_ROLE ?? '';
    const matchesRole = audience === 'all'
        || (audience === 'official' && ['official', 'pendamping'].includes(currentRole))
        || (audience === 'panitia' && currentRole === 'panitia')
        || (audience === 'official_panitia' && ['official', 'pendamping', 'panitia'].includes(currentRole));

    if (!matchesRole) {
        return;
    }

    Alpine.store('ui').pushNotification({
        tone: detail.priority === 'high' ? 'warning' : 'info',
        title: 'Pengumuman Baru Disiarkan',
        message: `${detail.title ?? 'Pengumuman'}${detail.body ? `: ${detail.body}` : ''}`,
    });
});

function scheduleTimeLabel(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const dateLabel = new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Jakarta',
    }).format(date);
    const timeLabel = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        timeZone: 'Asia/Jakarta',
    }).format(date);

    return `${dateLabel} pukul ${timeLabel} WIB`;
}

function pushScheduleNotification(detail) {
    if (detail.status === 'ongoing' && detail.source !== 'manual') {
        const key = `mtq-ongoing-schedule:${detail.id ?? detail.title}:${detail.starts_at ?? ''}`;

        try {
            if (window.sessionStorage.getItem(key) === 'shown') {
                return;
            }

            window.sessionStorage.setItem(key, 'shown');
        } catch {
            // If sessionStorage is unavailable, showing the notification once per page load is fine.
        }
    }

    const when = scheduleTimeLabel(detail.starts_at);
    const place = detail.venue ? ` di ${detail.venue}` : '';
    const time = when ? ` pada ${when}` : '';
    const stage = detail.stage ? ` (${detail.stage})` : '';
    const isOngoing = detail.status === 'ongoing';

    Alpine.store('ui').pushNotification({
        tone: isOngoing ? 'success' : 'info',
        title: isOngoing ? 'Jadwal Sedang Berlangsung' : 'Jadwal Disiarkan',
        message: `${detail.title ?? 'Jadwal'}${stage}${place}${time}.`,
    });
}

function notifyOngoingSchedulesOnPageLoad() {
    const schedules = Array.isArray(window.mtqOngoingSchedules) ? window.mtqOngoingSchedules : [];

    schedules.forEach((schedule) => {
        pushScheduleNotification({ ...schedule, status: 'ongoing', source: 'page-load' });
    });
}

async function fetchOngoingSchedules() {
    try {
        const url = window.mtqOngoingSchedulesUrl || '/jadwal/berlangsung';
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        const schedules = Array.isArray(payload.schedules) ? payload.schedules : [];

        schedules.forEach((schedule) => {
            pushScheduleNotification({ ...schedule, status: 'ongoing', source: schedule.source ?? 'poll' });
        });
    } catch (error) {
        console.warn('Ongoing schedule check failed.', error);
    }
}

function stopOngoingSchedulesFallbackPolling() {
    if (ongoingSchedulesFallbackDelayTimer) {
        window.clearTimeout(ongoingSchedulesFallbackDelayTimer);
        ongoingSchedulesFallbackDelayTimer = null;
    }

    if (ongoingSchedulesFallbackTimer) {
        window.clearInterval(ongoingSchedulesFallbackTimer);
        ongoingSchedulesFallbackTimer = null;
    }
}

function startOngoingSchedulesFallbackPolling() {
    if (realtimeConnected || ongoingSchedulesFallbackTimer) {
        return;
    }

    fetchOngoingSchedules();
    ongoingSchedulesFallbackTimer = window.setInterval(() => {
        if (realtimeConnected) {
            stopOngoingSchedulesFallbackPolling();
            return;
        }

        fetchOngoingSchedules();
    }, 60000);
}

function startOngoingScheduleChecks() {
    notifyOngoingSchedulesOnPageLoad();

    if (!reverbAppKey) {
        startOngoingSchedulesFallbackPolling();
        return;
    }

    if (realtimeConnected) {
        return;
    }

    ongoingSchedulesFallbackDelayTimer = window.setTimeout(() => {
        ongoingSchedulesFallbackDelayTimer = null;

        if (!realtimeConnected) {
            startOngoingSchedulesFallbackPolling();
        }
    }, 10000);
}

function runWhenReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
}

window.addEventListener('mtq-schedule-updated', (event) => {
    const detail = event.detail ?? {};

    pushScheduleNotification(detail);
});

runWhenReady(() => {
    startOngoingScheduleChecks();

    if (!document.getElementById('mtq-theme-toggle')) {
        const button = document.createElement('button');
        button.id = 'mtq-theme-toggle';
        button.type = 'button';
        button.className = 'theme-toggle fixed bottom-4 right-4 z-[60] inline-flex h-11 w-11 items-center justify-center rounded-full border border-cyan-400/20 bg-slate-950/85 text-cyan-100 shadow-[0_18px_40px_-20px_rgba(14,165,233,0.55)] backdrop-blur-xl transition hover:-translate-y-0.5 hover:border-cyan-300/40 hover:bg-slate-900';
        button.setAttribute('aria-label', initialTheme === 'light' ? 'Beralih ke tema gelap' : 'Beralih ke tema terang');
        button.title = initialTheme === 'light' ? 'Beralih ke tema gelap' : 'Beralih ke tema terang';
        button.innerHTML = renderThemeToggle(initialTheme);
        button.addEventListener('click', () => {
            Alpine.store('ui').toggleTheme();
        });
        document.body.appendChild(button);
    }
});

Alpine.start();
