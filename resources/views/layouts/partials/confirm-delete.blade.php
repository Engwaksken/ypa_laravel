{{-- Global Bootstrap delete-confirmation modal (replaces native confirm()).
     Usage: <form method="POST" action="..." class="ypa-confirm-delete"
                 data-confirm-title="Delete product?"
                 data-confirm-message="This cannot be undone.">
     The submit is intercepted, the modal is shown, and the form is
     submitted only after confirmation.
     AJAX usage: YpaConfirmDelete.open({title, message, trigger, key, onConfirm}).
     onConfirm(attempt) returns a Promise of a success message. Guard caller-side
     effects with attempt.isActive(). Only errors marked definite permit retry;
     timeout/transport/invalid-response outcomes require a record-state check.
     Only the shared button invokes the callback; cancel never invokes it. --}}
<div class="modal fade" id="ypaConfirmDeleteModal" tabindex="-1" aria-labelledby="ypaConfirmDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ypaConfirmDeleteLabel">
                    <i class="fa-solid fa-trash-can text-danger me-2" aria-hidden="true"></i>
                    <span id="ypaConfirmDeleteTitle">Delete this record?</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="ypaConfirmDeleteMessage">This action cannot be undone.</p>
                <div id="ypaConfirmDeleteAlert" class="alert mt-3 mb-0" role="alert" hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="ypaConfirmDeleteButton">
                    <i class="fa-solid fa-trash-can me-1" aria-hidden="true"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var pending = null;
        var restoreTrigger = null;
        var busy = false;
        var attempt = null;
        var uncertainRecords = new Set();
        var deletedRecords = new Set();
        var uncertainMessage = 'The deletion outcome is unknown. The server may still have completed it. Close this dialog and refresh/check the current record state before taking further action; do not retry blindly.';
        var modalEl = document.getElementById('ypaConfirmDeleteModal');
        var titleEl = document.getElementById('ypaConfirmDeleteTitle');
        var messageEl = document.getElementById('ypaConfirmDeleteMessage');
        var confirmBtn = document.getElementById('ypaConfirmDeleteButton');
        var alertEl = document.getElementById('ypaConfirmDeleteAlert');

        if (!modalEl || !confirmBtn) return;

        function hasBootstrap() { return !!(window.bootstrap && bootstrap.Modal); }
        function lock(locked) {
            busy = locked;
            confirmBtn.disabled = locked;
            modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) { button.disabled = locked; });
            modalEl.setAttribute('aria-busy', locked ? 'true' : 'false');
            confirmBtn.innerHTML = locked
                ? '<i class="fa-solid fa-spinner fa-spin me-1" aria-hidden="true"></i> Deleting...'
                : '<i class="fa-solid fa-trash-can me-1" aria-hidden="true"></i> Delete';
        }
        function feedback(message, success) {
            alertEl.className = 'alert mt-3 mb-0 alert-' + (success ? 'success' : 'danger');
            // textContent is equivalent to escaped alerts, including server messages.
            alertEl.textContent = message;
            alertEl.hidden = false;
        }
        function clear() {
            if (attempt) { attempt.active = false; clearTimeout(attempt.timer); }
            attempt = null;
            pending = null;
            restoreTrigger = null;
            lock(false);
            alertEl.hidden = true;
            alertEl.textContent = '';
        }
        function ambiguous(context) {
            if (context.key) uncertainRecords.add(context.key);
            if (attempt) { attempt.active = false; clearTimeout(attempt.timer); }
            lock(false);
            confirmBtn.disabled = true;
            feedback(uncertainMessage, false);
            if (!hasBootstrap()) { window.alert(uncertainMessage); clear(); }
        }
        function run() {
            if (!pending || busy || confirmBtn.disabled) return;
            var context = pending;
            if (context.form) {
                var form = context.form;
                form.dataset.confirmed = '1';
                restoreTrigger = context.trigger;
                pending = null;
                confirmBtn.disabled = true;
                if (hasBootstrap()) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                else clear();
                // Preserve the existing ordinary-form submission contract.
                form.submit();
                return;
            }
            lock(true);
            alertEl.hidden = true;
            var current = {active: true, timer: null};
            attempt = current;
            current.isActive = function () { return current.active && attempt === current && pending === context; };
            current.timer = setTimeout(function () {
                if (current.isActive()) ambiguous(context);
            }, 30000);
            Promise.resolve().then(function () { return context.onConfirm(current); }).then(function (message) {
                if (!current.isActive()) return;
                clearTimeout(current.timer);
                if (context.key) deletedRecords.add(context.key);
                feedback(message || 'Deleted successfully.', true);
                lock(false);
                confirmBtn.disabled = true;
            }).catch(function (error) {
                if (!current.isActive()) return;
                clearTimeout(current.timer);
                if (!error || !error.definite) { ambiguous(context); return; }
                current.active = false;
                var message = error && error.message ? error.message : 'Delete failed.';
                feedback(message, false);
                lock(false);
                if (!hasBootstrap()) {
                    window.alert(message);
                    clear();
                }
            });
        }
        function open(options) {
            if (pending || restoreTrigger || busy || !options || (!options.form && typeof options.onConfirm !== 'function')) return false;
            pending = {
                form: options.form || null,
                onConfirm: options.onConfirm,
                key: options.key || null,
                trigger: options.trigger || document.activeElement
            };
            titleEl.textContent = options.title || 'Delete this record?';
            messageEl.textContent = options.message || 'This action cannot be undone.';
            alertEl.hidden = true;
            lock(false);
            if (pending.key && uncertainRecords.has(pending.key)) {
                confirmBtn.disabled = true;
                feedback(uncertainMessage, false);
            }
            if (pending.key && deletedRecords.has(pending.key)) {
                confirmBtn.disabled = true;
                feedback('This record was deleted successfully. Refresh to see the current listing.', true);
            }
            if (hasBootstrap()) bootstrap.Modal.getOrCreateInstance(modalEl).show(pending.trigger);
            else if (pending.key && deletedRecords.has(pending.key)) { window.alert(alertEl.textContent); clear(); }
            else if (pending.key && uncertainRecords.has(pending.key)) { window.alert(uncertainMessage); clear(); }
            else if (window.confirm(messageEl.textContent)) run();
            else clear();
            return true;
        }
        window.YpaConfirmDelete = {open: open};
        modalEl.addEventListener('hide.bs.modal', function (event) {
            if (busy) { event.preventDefault(); return; }
            // Clear the callback at the start of dismissal, not after the fade.
            restoreTrigger = pending ? pending.trigger : restoreTrigger;
            if (attempt) { attempt.active = false; clearTimeout(attempt.timer); }
            pending = null;
            confirmBtn.disabled = true;
        });
        modalEl.addEventListener('hidden.bs.modal', clear);
        document.addEventListener('submit', function (event) {
            var form = event.target instanceof HTMLFormElement ? event.target : null;
            if (!form || !form.classList.contains('ypa-confirm-delete') || form.dataset.confirmed === '1') return;
            event.preventDefault();
            open({form: form, trigger: event.submitter || document.activeElement,
                title: form.dataset.confirmTitle, message: form.dataset.confirmMessage});
        });
        confirmBtn.addEventListener('click', run);
    })();
</script>
