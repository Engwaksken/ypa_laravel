{{-- Global Bootstrap delete-confirmation modal (replaces native confirm()).
     Usage: <form method="POST" action="..." class="ypa-confirm-delete"
                 data-confirm-title="Delete product?"
                 data-confirm-message="This cannot be undone.">
     The submit is intercepted, the modal is shown, and the form is
     submitted only after confirmation. --}}
<div class="modal fade" id="ypaConfirmDeleteModal" tabindex="-1" aria-labelledby="ypaConfirmDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ypaConfirmDeleteLabel">
                    <i class="fas fa-trash-can text-danger me-2"></i>
                    <span id="ypaConfirmDeleteTitle">Delete this record?</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="ypaConfirmDeleteMessage">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="ypaConfirmDeleteButton">
                    <i class="fas fa-trash-can me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var pendingForm = null;
        var modalEl = document.getElementById('ypaConfirmDeleteModal');
        var titleEl = document.getElementById('ypaConfirmDeleteTitle');
        var messageEl = document.getElementById('ypaConfirmDeleteMessage');
        var confirmBtn = document.getElementById('ypaConfirmDeleteButton');

        if (!modalEl || !confirmBtn) return;

        document.addEventListener('submit', function (event) {
            var form = event.target instanceof HTMLFormElement ? event.target : null;
            if (!form || !form.classList.contains('ypa-confirm-delete')) return;
            if (form.dataset.confirmed === '1') return;

            event.preventDefault();
            pendingForm = form;

            titleEl.textContent = form.dataset.confirmTitle || 'Delete this record?';
            messageEl.textContent = form.dataset.confirmMessage || 'This action cannot be undone.';

            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else if (confirm(messageEl.textContent)) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });

        confirmBtn.addEventListener('click', function () {
            if (!pendingForm) return;

            var form = pendingForm;
            pendingForm = null;
            form.dataset.confirmed = '1';

            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }

            form.submit();
        });
    })();
</script>
