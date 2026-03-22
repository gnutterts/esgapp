import './bootstrap';

// ─── Dark mode ──────────────────────────────────────────────────────────────
// Reads 'theme' from localStorage ('dark' | 'light' | 'system' / missing).
// Applies or removes the .dark class on <html> before first paint (inline
// script in the layout handles the critical path; this module manages
// the toggle button interactions at runtime).

export function getTheme() {
    return localStorage.getItem('theme') ?? 'system';
}

export function applyTheme(theme) {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const useDark = theme === 'dark' || (theme === 'system' && prefersDark);
    document.documentElement.classList.toggle('dark', useDark);
}

export function setTheme(theme) {
    localStorage.setItem('theme', theme);
    applyTheme(theme);
    updateToggleUI(theme);
}

function updateToggleUI(theme) {
    document.querySelectorAll('[data-theme-btn]').forEach(btn => {
        const active = btn.dataset.themeBtn === theme;
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        btn.classList.toggle('theme-btn-active', active);
        btn.classList.toggle('theme-btn-inactive', !active);
    });
}

// Wire up theme toggle buttons once DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const current = getTheme();
    updateToggleUI(current);

    document.querySelectorAll('[data-theme-btn]').forEach(btn => {
        btn.addEventListener('click', () => setTheme(btn.dataset.themeBtn));
    });

    // React to OS-level changes when 'system' is selected
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getTheme() === 'system') applyTheme('system');
    });
});

// ─── Double-submit prevention ────────────────────────────────────────────────
document.addEventListener('submit', function (e) {
    const form = e.target;

    // Skip GET forms (search, filters)
    if (form.method.toUpperCase() === 'GET') return;

    const buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    buttons.forEach(function (btn) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    });

    // Re-enable after 5s in case of client-side validation failure or network error
    setTimeout(function () {
        buttons.forEach(function (btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        });
    }, 5000);
});
