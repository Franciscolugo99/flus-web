document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-confirm]');
    if (!trigger) return;

    const message = trigger.getAttribute('data-confirm') || '¿Confirmar esta acción?';
    if (!window.confirm(message)) {
        event.preventDefault();
    }
});
