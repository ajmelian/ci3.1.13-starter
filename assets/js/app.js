(() => {
    'use strict';

    const lockForms = document.querySelectorAll('[data-session-lock]');
    lockForms.forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = button.dataset.loadingText ?? 'Procesando...';
            }
        });
    });
})();
