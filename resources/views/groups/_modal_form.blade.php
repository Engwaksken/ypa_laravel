{{--
    Add/Edit group modal. Requires $branches and $mobilizers.
    Open with openGroupModal() (add) or openGroupEdit(button) where the button carries
    data-url (update route) and data-record (JSON of form values).
--}}
<div class="modal fade" id="groupModal" tabindex="-1" aria-labelledby="groupModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="groupForm" method="POST" action="{{ route('groups.store') }}">
                @csrf
                <input type="hidden" name="_method" id="groupMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="groupModalTitle">Add Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('groups._form_fields', ['record' => null, 'useOld' => false])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('groupModal');
    const form = document.getElementById('groupForm');
    const storeUrl = @json(route('groups.store'));
    YpaGroupForm.bind(form);
    // Re-sync dependent fields whenever the modal opens (covers validation-error reopen).
    modalEl.addEventListener('show.bs.modal', function () { YpaGroupForm.sync(form); });

    function show() {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    window.openGroupModal = function () {
        form.reset();
        YpaGroupForm.clearErrors(form);
        form.action = storeUrl;
        document.getElementById('groupMethod').value = 'POST';
        document.getElementById('groupModalTitle').textContent = 'Add Group';
        YpaGroupForm.sync(form);
        show();
    };

    window.openGroupEdit = function (btn) {
        form.reset();
        YpaGroupForm.clearErrors(form);
        form.action = btn.dataset.url;
        document.getElementById('groupMethod').value = 'PUT';
        document.getElementById('groupModalTitle').textContent = 'Edit Group';
        let record = {};
        try { record = JSON.parse(btn.dataset.record || '{}'); } catch (e) { record = {}; }
        YpaGroupForm.fill(form, record);
        show();
    };
})();
</script>
@endpush
