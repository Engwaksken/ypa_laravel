{{-- Add/Edit contract template modal. Requires $projects and $projectCategories. --}}
<div class="modal fade" id="contractTemplateModal" tabindex="-1" aria-labelledby="contractTemplateModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="contractTemplateForm" method="POST" action="{{ route('contract-templates.store') }}">
                @csrf
                <input type="hidden" name="_method" id="contractTemplateMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="contractTemplateModalTitle">Add Contract Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php
                        $tabs = [
                            ['id' => 'ct-tab-details', 'label' => 'Details', 'fields' => ['template_name', 'template_key', 'project_id', 'project_type_id', 'project_category_id', 'version', 'is_active']],
                            ['id' => 'ct-tab-content', 'label' => 'Content', 'fields' => ['cover_page', 'template_body', 'contract_footer', 'contract_signature', 'template_sections']],
                        ];
                        $activeTabId = $tabs[0]['id'];
                        foreach ($tabs as $tab) {
                            foreach ($tab['fields'] as $field) {
                                if ($errors->has($field)) { $activeTabId = $tab['id']; break 2; }
                            }
                        }
                    @endphp
                    <ul class="nav nav-tabs ypa-tabs mb-3" role="tablist">
                        @foreach($tabs as $tab)
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link @if($tab['id'] === $activeTabId) active @endif" id="{{ $tab['id'] }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tab['id'] }}" role="tab" aria-controls="{{ $tab['id'] }}" aria-selected="{{ $tab['id'] === $activeTabId ? 'true' : 'false' }}">{{ $tab['label'] }}</button>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade @if($activeTabId === 'ct-tab-details') show active @endif" id="ct-tab-details" role="tabpanel" aria-labelledby="ct-tab-details-tab">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="ctName">Template Name</label>
                                    <input type="text" name="template_name" id="ctName" class="form-control" maxlength="255" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ctKey">Template Key</label>
                                    <input type="text" name="template_key" id="ctKey" class="form-control" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ctProject">Specific Project</label>
                                    <select name="project_id" id="ctProject" class="form-select">
                                        <option value="">All matching projects (default)</option>
                                        @foreach($projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ctProjectType">Project Type ID</label>
                                    <input type="number" min="1" name="project_type_id" id="ctProjectType" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ctProjectCategory">Project Category</label>
                                    <select name="project_category_id" id="ctProjectCategory" class="form-select">
                                        <option value="">-- None --</option>
                                        @foreach($projectCategories as $category)
                                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ctVersion">Version</label>
                                    <input type="number" min="1" name="version" id="ctVersion" value="1" class="form-control">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="ctActive" checked>
                                        <label class="form-check-label" for="ctActive">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade @if($activeTabId === 'ct-tab-content') show active @endif" id="ct-tab-content" role="tabpanel" aria-labelledby="ct-tab-content-tab">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="ctCover">Cover Page</label>
                                    <textarea name="cover_page" id="ctCover" class="form-control font-monospace" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="ctBody">Template Body</label>
                                    <textarea name="template_body" id="ctBody" class="form-control font-monospace" rows="10" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ctFooter">Contract Footer</label>
                                    <textarea name="contract_footer" id="ctFooter" class="form-control font-monospace" rows="3"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="ctSignature">Contract Signature</label>
                                    <textarea name="contract_signature" id="ctSignature" class="form-control font-monospace" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="ctSections">Template Sections (JSON)</label>
                                    <textarea name="template_sections" id="ctSections" class="form-control font-monospace" rows="4"></textarea>
                                </div>
                            </div>
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
    function contractTemplateModalShow(trigger) {
        YpaFormTabs.showErrorTab(document.getElementById('contractTemplateForm'));
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('contractTemplateModal')).show(trigger);
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
            ctName: d.name, ctKey: d.key, ctProject: d.project, ctProjectType: d.projectType, ctProjectCategory: d.projectCategory,
            ctVersion: d.version || '1', ctCover: d.cover, ctBody: d.body, ctFooter: d.footer,
            ctSignature: d.signature, ctSections: d.sections
        };
        Object.keys(map).forEach(function (id) {
            document.getElementById(id).value = map[id] || '';
        });
        document.getElementById('ctActive').checked = d.active === '1';
        contractTemplateModalShow(btn);
    }
</script>
@endpush
