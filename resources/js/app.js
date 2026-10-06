import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Diamkan saja kalau gagal — bukan fitur kritikal, app tetap jalan normal tanpa PWA.
        });
    });
}
