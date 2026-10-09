{{-- Keep submitted values and reopen ordinary POST modals after validation redirects. --}}
<script>
(() => {
    const previous = {{ Illuminate\Support\Js::from(session()->getOldInput()) }};
    const errors = {{ Illuminate\Support\Js::from($errors->all()) }};
    const modalForms = [...document.querySelectorAll('.modal form[id][method="POST"]')];
    modalForms.forEach(form => {
        form.addEventListener('submit', event => {
            if (event.defaultPrevented) return;
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            for (const [name, value] of Object.entries({_modal_form: form.id, _modal_action: form.action})) {
                let input = form.querySelector('input[name="' + name + '"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    form.appendChild(input);
                }
                input.value = value;
            }
            form.dataset.submitting = '1';
            form.querySelectorAll('button[type="submit"]').forEach(button => {
                if (!button.disabled) {
                    button.dataset.submitLocked = '1';
                    button.disabled = true;
                }
            });
        });
    });
    if (typeof window !== 'undefined') window.addEventListener('pageshow', () => {
        modalForms.forEach(form => {
            delete form.dataset.submitting;
            form.querySelectorAll('[data-submit-locked]').forEach(button => {
                button.disabled = false;
                delete button.dataset.submitLocked;
            });
        });
    });
    if (!errors.length || !previous._modal_form) return;
    const form = modalForms.find(candidate => candidate.id === previous._modal_form);
    if (!form) return;
    let action;
    try { action = new URL(previous._modal_action, location.href); } catch { return; }
    const base = new URL(form.action, location.href);
    if (action.origin !== location.origin || !(action.pathname === base.pathname || action.pathname.startsWith(base.pathname + '/'))) return;
    const method = previous._method || 'POST';
    if (!['POST', 'PUT', 'PATCH'].includes(method)) return;
    form.action = action.href;
    [...form.elements].forEach(input => {
        if (!input.name || input.name.startsWith('_modal_') || input.name === '_token' || input.type === 'file') return;
        if (input.type === 'hidden' && input.name !== '_method') return;
        if (!Object.prototype.hasOwnProperty.call(previous, input.name)) return;
        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = String(previous[input.name]) === input.value;
        } else {
            input.value = previous[input.name] ?? '';
        }
    });
    const modalElement = form.closest('.modal');
    if (method !== 'POST') {
        const title = modalElement.querySelector('.modal-title');
        if (title) title.textContent = title.textContent.replace(/^Add\b/, 'Edit');
    }
    const message = document.createElement('div');
    message.className = 'alert alert-danger';
    message.setAttribute('role', 'alert');
    message.textContent = errors.join(' ');
    modalElement.querySelector('.modal-body')?.prepend(message);
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
})();
</script>
