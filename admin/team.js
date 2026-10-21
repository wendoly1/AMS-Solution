(() => {
    'use strict';
    const list = document.querySelector('[data-team-list]');
    if (!list) return;
    const template = document.querySelector('[data-team-template]');
    const add = document.querySelector('[data-team-add]');
    const undo = document.querySelector('[data-team-undo]');
    const empty = document.querySelector('[data-team-empty]');
    const status = document.querySelector('[data-team-status]');
    const removed = [];
    const cards = () => Array.from(list.querySelectorAll('[data-team-card]'));
    let nextIndex = cards().reduce((max, card) => Math.max(max, Number(card.dataset.teamIndex) + 1), 0);

    function refresh() {
        const items = cards();
        items.forEach((card, index) => {
            card.querySelector('[data-team-title]').textContent = `Abogado ${index + 1}`;
            const remove = card.querySelector('[data-team-remove]');
            remove.hidden = false;
            remove.setAttribute('aria-label', `Eliminar espacio de Abogado ${index + 1}`);
        });
        empty.hidden = items.length > 0;
        undo.hidden = removed.length === 0;
        add.disabled = items.length >= 100;
    }

    add.hidden = false;
    add.addEventListener('click', () => {
        if (cards().length >= 100) return;
        const index = nextIndex++;
        const markup = template.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__NUMBER__', String(cards().length + 1));
        const fragment = document.createElement('template');
        fragment.innerHTML = markup;
        const card = fragment.content.querySelector('[data-team-card]');
        // Índice estable: mantiene cada foto asociada a su perfil al borrar otras filas.
        card.querySelector('input[name$="[slug]"]').value = `abogado-${Date.now().toString(36)}-${index}`;
        list.append(card);
        refresh();
        status.textContent = 'Espacio agregado. Completa el nombre y guarda los cambios para publicarlo.';
        card.querySelector('input[name$="[name]"]').focus();
    });

    list.addEventListener('click', event => {
        const button = event.target.closest('[data-team-remove]');
        if (!button) return;
        const card = button.closest('[data-team-card]');
        removed.push({ card, next: card.nextElementSibling });
        card.remove();
        refresh();
        status.textContent = 'Espacio eliminado de la lista. Puedes deshacerlo antes de guardar.';
        (add.disabled ? undo : add).focus();
    });

    undo.addEventListener('click', () => {
        if (!removed.length) return;
        if (cards().length >= 100) {
            status.textContent = 'El máximo es de 100 perfiles. Elimina un espacio para restaurar este abogado.';
            return;
        }
        const { card, next } = removed.pop();
        list.insertBefore(card, next?.parentNode === list ? next : null);
        refresh();
        status.textContent = 'Espacio restaurado con sus datos y fotografía.';
        card.querySelector('input[name$="[name]"]').focus();
    });
    refresh();
})();
