{{-- Keep submitted values and reopen ordinary POST modals after validation redirects. --}}
<script>
(() => {
    const previous = {{ Illuminate\Support\Js::from(session()->getOldInput()) }};
    const errors = {{ Illuminate\Support\Js::from($errors->all()) }};
    const modalForms = [...document.querySelectorAll('.modal form[id][method="POST"], .modal form[id][data-modal-validation]')];
    modalForms.forEach(form => {
        form.addEventListener('submit', event => {
            if (event.defaultPrevented) return;
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            for (const [name, value] of Object.entries({_modal_form: form.id, _modal_action: form.action,
                _modal_context: form.dataset.modalContext || ''})) {
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
    let action, base;
    try {
        action = new URL(previous._modal_action, location.href);
        base = new URL(form.action, location.href);
    } catch { return; }
    const basePath = base.pathname.replace(/\/$/, '');
    if (action.origin !== location.origin || action.username || action.password ||
        !(action.pathname === base.pathname || action.pathname.startsWith(basePath + '/'))) return;
    const method = previous._method || 'POST';
    if (!['POST', 'PUT', 'PATCH'].includes(method)) return;
    form.action = action.href;
    let context = {};
    try {
        const parsed = JSON.parse(previous._modal_context || '{}');
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) context = parsed;
    } catch { /* Malformed descriptive context must not prevent value restoration. */ }
    const detail = {action: action.href, method, previous, context, notice: ''};
    function notify(name) {
        if (typeof CustomEvent === 'function' && typeof form.dispatchEvent === 'function') {
            form.dispatchEvent(new CustomEvent(name, {detail}));
        }
    }
    // Page hooks initialize current-record constraints before applying submitted values.
    notify('ypa:modal-restoring');
    [...form.elements].forEach(input => {
        if (!input.name || input.name.startsWith('_modal_') || input.name === '_token' || input.type === 'file') return;
        if (input.type === 'hidden' && input.name !== '_method') return;
        if (input.name === '_method') { input.value = method; return; }
        if (input.type === 'password' || input.disabled) return;
        const present = Object.prototype.hasOwnProperty.call(previous, input.name);
        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = present && (Array.isArray(previous[input.name])
                ? previous[input.name].map(String).includes(input.value)
                : String(previous[input.name]) === input.value);
        } else if (present && input.multiple && input.options && Array.isArray(previous[input.name])) {
            const selected = previous[input.name].map(String);
            [...input.options].forEach(option => { option.selected = selected.includes(option.value); });
        } else if (present && (previous[input.name] == null || ['string', 'number', 'boolean'].includes(typeof previous[input.name]))) {
            input.value = previous[input.name] ?? '';
        }
    });
    // Dependent visibility/required state is synced without resetting the form.
    notify('ypa:modal-restored');
    const modalElement = form.closest('.modal');
    if (method !== 'POST') {
        const title = modalElement.querySelector('.modal-title');
        if (title) title.textContent = title.textContent.replace(/^Add\b/, 'Edit');
    }
    const message = document.createElement('div');
    message.className = 'alert alert-danger';
    message.setAttribute('role', 'alert');
    message.textContent = errors.join(' ') + (detail.notice ? ' ' + detail.notice : '');
    modalElement.querySelector('.modal-body')?.prepend(message);
    if ([...form.elements].some(input => input.type === 'file' && !input.disabled)) {
        const notice = document.createElement('div');
        notice.className = 'alert alert-warning';
        notice.setAttribute('role', 'status');
        notice.textContent = 'Files cannot be restored after validation. Please reselect any file you intended to upload.';
        modalElement.querySelector('.modal-body')?.prepend(notice);
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    } else {
        // Keep errors and recovered fields reachable when Bootstrap fails to load.
        modalElement.hidden = false;
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        modalElement.removeAttribute('aria-hidden');
        modalElement.setAttribute('role', 'region');
    }
})();
</script>
