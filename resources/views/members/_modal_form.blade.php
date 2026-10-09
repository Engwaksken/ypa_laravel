{{--
    Add/Edit member modal. Requires $branches and $mobilizers.
    Open with openMemberModal() (add) or openMemberEdit(button) where the button carries
    data-url (update route) and data-record (JSON of form values).
--}}
<div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="memberForm" method="POST" action="{{ route('members.store') }}">
                @csrf
                <input type="hidden" name="_method" id="memberMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="memberModalTitle">Add Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('members._form_fields', ['record' => null, 'useOld' => false])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('memberModal');
    const form = document.getElementById('memberForm');
    const storeUrl = @json(route('members.store'));
    YpaMemberForm.bind(form);
    // Re-sync dependent fields whenever the modal opens (covers validation-error reopen).
    modalEl.addEventListener('show.bs.modal', function () { YpaMemberForm.sync(form); });

    function show() {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    window.openMemberModal = function () {
        form.reset();
        YpaMemberForm.clearErrors(form);
        form.action = storeUrl;
        document.getElementById('memberMethod').value = 'POST';
        document.getElementById('memberModalTitle').textContent = 'Add Member';
        YpaMemberForm.sync(form);
        show();
    };

    window.openMemberEdit = function (btn) {
        form.reset();
        YpaMemberForm.clearErrors(form);
        form.action = btn.dataset.url;
        document.getElementById('memberMethod').value = 'PUT';
        document.getElementById('memberModalTitle').textContent = 'Edit Member';
        let record = {};
        try { record = JSON.parse(btn.dataset.record || '{}'); } catch (e) { record = {}; }
        YpaMemberForm.fill(form, record);
        show();
    };
})();
</script>
@endpush
