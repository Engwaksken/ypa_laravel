{{-- Add/Edit contract template modal. Requires $projectCategories. --}}
<div class="modal fade" id="contractTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="contractTemplateForm" method="POST" action="{{ route('contract-templates.store') }}">
                @csrf
                <input type="hidden" name="_method" id="contractTemplateMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="contractTemplateModalTitle">Add Contract Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-section-title">Details</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="template_name" id="ctName" class="form-control" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Template Key</label>
                            <input type="text" name="template_key" id="ctKey" class="form-control" maxlength="255">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Project Type ID</label>
                            <input type="number" min="1" name="project_type_id" id="ctProjectType" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Project Category</label>
                            <select name="project_category_id" id="ctProjectCategory" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($projectCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Version</label>
                            <input type="number" min="1" name="version" id="ctVersion" value="1" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" class="form-check-input" name="is_active" value="1" id="ctActive" checked>
                                <label class="form-check-label" for="ctActive">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section-title mt-4">Content</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Cover Page</label>
                            <textarea name="cover_page" id="ctCover" class="form-control font-monospace" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Template Body</label>
                            <textarea name="template_body" id="ctBody" class="form-control font-monospace" rows="10" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contract Footer</label>
                            <textarea name="contract_footer" id="ctFooter" class="form-control font-monospace" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contract Signature</label>
                            <textarea name="contract_signature" id="ctSignature" class="form-control font-monospace" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Template Sections (JSON)</label>
                            <textarea name="template_sections" id="ctSections" class="form-control font-monospace" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function contractTemplateModalShow() {
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('contractTemplateModal')).show();
        }
    }

    function openContractTemplateModal() {
        var form = document.getElementById('contractTemplateForm');
        form.reset();
        form.action = "{{ route('contract-templates.store') }}";
        document.getElementById('contractTemplateMethod').value = 'POST';
        document.getElementById('contractTemplateModalTitle').textContent = 'Add Contract Template';
        contractTemplateModalShow();
    }

    function openContractTemplateEdit(btn) {
        var form = document.getElementById('contractTemplateForm');
        var d = btn.dataset;
        form.reset();
        form.action = d.url;
        document.getElementById('contractTemplateMethod').value = 'PUT';
        document.getElementById('contractTemplateModalTitle').textContent = 'Edit Contract Template';
        var map = {
            ctName: d.name, ctKey: d.key, ctProjectType: d.projectType, ctProjectCategory: d.projectCategory,
            ctVersion: d.version || '1', ctCover: d.cover, ctBody: d.body, ctFooter: d.footer,
            ctSignature: d.signature, ctSections: d.sections
        };
        Object.keys(map).forEach(function (id) {
            document.getElementById(id).value = map[id] || '';
        });
        document.getElementById('ctActive').checked = d.active === '1';
        contractTemplateModalShow();
    }
</script>
@endpush
