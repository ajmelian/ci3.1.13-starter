(() => {
    'use strict';

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form.matches('[data-confirm]')) {
            const message = form.getAttribute('data-confirm');
            if (!window.confirm(message ?? '¿Estás seguro?')) {
                event.preventDefault();
            }
        }
    });
})();
