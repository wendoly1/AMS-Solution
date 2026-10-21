(() => {
    'use strict';
    const form = document.querySelector('form[action="save.php"]');
    const state = document.querySelector('[data-editor-state]');
    let dirty = false;
    let leaving = false;
    function snapshot() {
        return JSON.stringify(Array.from(new FormData(form), ([name, value]) => [name, value instanceof File ? (value.name ? [value.name, value.size, value.lastModified] : null) : value]));
    }
    if (form) {
        const initial = snapshot();
        function update() {
            dirty = snapshot() !== initial;
            if (state) {
                state.textContent = dirty ? 'Cambios sin guardar' : 'Sin cambios pendientes';
                state.classList.toggle('is-dirty', dirty);
            }
        }
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        new MutationObserver(update).observe(form, { childList: true, subtree: true });
        form.querySelectorAll('.save-row').forEach(row => {
            const preview = document.createElement('button');
            preview.type = 'submit';
            preview.textContent = 'Vista previa';
            preview.className = 'button-secondary';
            preview.formAction = 'preview.php';
            preview.formTarget = 'ams-content-preview';
            preview.formEnctype = 'multipart/form-data';
            preview.dataset.preview = 'true';
            row.prepend(preview);
        });
        form.addEventListener('submit', event => {
            if (event.submitter?.dataset.preview !== 'true') leaving = true;
        });
        window.addEventListener('beforeunload', event => {
            if (!dirty || leaving) return;
            event.preventDefault();
            event.returnValue = '';
        });
        document.addEventListener('click', event => {
            const link = event.target.closest('a[href]');
            if (!dirty || !link || link.target === '_blank' || link.getAttribute('href').startsWith('#') || event.ctrlKey || event.metaKey || event.shiftKey) return;
            if (!window.confirm('Tienes cambios sin guardar. ¿Quieres salir y descartarlos?')) {
                event.preventDefault();
                event.stopImmediatePropagation();
            } else leaving = true;
        }, true);
        window.addEventListener('pageshow', () => { leaving = false; update(); });
    }

    document.querySelectorAll('form[data-confirm]').forEach(item => item.addEventListener('submit', event => {
        if (!window.confirm(item.dataset.confirm)) event.preventDefault();
    }));

    // La imagen se muestra localmente; aún no se ha publicado ni subido al sitio.
    const imageState = new WeakMap();
    document.addEventListener('change', event => {
        const input = event.target;
        if (!input.matches('input[type="file"][accept*="image/"]')) return;
        const container = input.closest('.profile-photo-editor') || input.closest('label');
        let record = imageState.get(input);
        if (!record) {
            let image = container.querySelector('.image-preview');
            let created = false;
            if (!image) {
                image = document.createElement('img');
                image.className = 'image-preview';
                image.alt = 'Fotografía seleccionada';
                input.before(image);
                created = true;
            }
            const note = document.createElement('span');
            note.className = 'image-selection-note';
            note.setAttribute('role', 'status');
            input.after(note);
            record = { image, original: image.getAttribute('src'), created, note, url: null };
            imageState.set(input, record);
        }
        const { image, note } = record;
        input.setCustomValidity('');
        if (record.url) URL.revokeObjectURL(record.url);
        const file = input.files[0];
        if (!file) {
            image.onload = null;
            image.onerror = null;
            if (record.original) image.src = record.original;
            image.hidden = record.created;
            const placeholder = container.querySelector('.photo-placeholder');
            if (placeholder) placeholder.hidden = false;
            note.textContent = '';
            return;
        }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            image.onload = null;
            image.onerror = null;
            if (record.original) image.src = record.original;
            image.hidden = record.created;
            input.setCustomValidity('Selecciona un JPG, PNG o WebP de hasta 5 MB.');
            note.textContent = 'Archivo inválido: usa JPG, PNG o WebP de hasta 5 MB.';
            return;
        }
        input.setCustomValidity('');
        record.url = URL.createObjectURL(file);
        image.hidden = false;
        image.onload = () => { note.textContent = `${file.name} · ${image.naturalWidth} × ${image.naturalHeight} px · Sin guardar`; };
        image.onerror = () => {
            input.setCustomValidity('El archivo no se pudo abrir como imagen.');
            note.textContent = 'No se pudo leer la imagen. Selecciona otro archivo.';
        };
        image.src = record.url;
        const placeholder = container.querySelector('.photo-placeholder');
        if (placeholder) placeholder.hidden = true;
    });
})();
