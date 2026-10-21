(() => {
    'use strict';
    const list = document.querySelector('[data-services-list]');
    if (!list) return;
    const template = document.querySelector('[data-services-template]');
    const add = document.querySelector('[data-services-add]');
    const undo = document.querySelector('[data-services-undo]');
    const empty = document.querySelector('[data-services-empty]');
    const status = document.querySelector('[data-services-status]');
    const removed = [];
    const cards = () => Array.from(list.querySelectorAll('[data-services-card]'));
    let nextIndex = cards().reduce((max, card) => Math.max(max, Number(card.dataset.servicesIndex) + 1), 0);

    function refresh() {
        const items = cards();
        items.forEach((card, index) => {
            card.querySelector('[data-services-number]').textContent = `ÁREA LEGAL ${index + 1}`;
            card.querySelector('[data-services-title]').textContent = card.querySelector('input[name$="[name]"]').value.trim() || 'Nueva área legal';
            const remove = card.querySelector('[data-services-remove]');
            remove.hidden = false;
            remove.setAttribute('aria-label', `Eliminar área legal ${index + 1}`);
        });
        empty.hidden = items.length > 0;
        undo.hidden = removed.length === 0;
        add.disabled = items.length >= 100;
    }

    add.hidden = false;
    add.addEventListener('click', () => {
        if (cards().length >= 100) return;
        const index = nextIndex++;
        const fragment = document.createElement('template');
        fragment.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__NUMBER__', String(cards().length + 1));
        const card = fragment.content.querySelector('[data-services-card]');
        card.querySelector('input[name$="[slug]"]').value = `area-${Date.now().toString(36)}-${index}`;
        list.append(card);
        refresh();
        status.textContent = 'Área agregada. Completa el nombre y guarda los cambios para publicarla.';
        card.querySelector('input[name$="[name]"]').focus();
    });

    list.addEventListener('input', event => {
        if (event.target.matches('input[name$="[name]"]')) refresh();
    });

    list.addEventListener('click', event => {
        const button = event.target.closest('[data-services-remove]');
        if (!button) return;
        const card = button.closest('[data-services-card]');
        removed.push({ card, next: card.nextElementSibling });
        card.remove();
        refresh();
        status.textContent = 'Área eliminada de la lista. Puedes deshacerlo antes de guardar.';
        (add.disabled ? undo : add).focus();
    });

    undo.addEventListener('click', () => {
        if (!removed.length) return;
        if (cards().length >= 100) {
            status.textContent = 'El máximo es de 100 áreas. Elimina un espacio para restaurar esta área.';
            return;
        }
        const { card, next } = removed.pop();
        list.insertBefore(card, next?.parentNode === list ? next : null);
        refresh();
        status.textContent = 'Área restaurada con todo su contenido.';
        card.querySelector('input[name$="[name]"]').focus();
    });
    refresh();
})();
