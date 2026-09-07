@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Template Name</label>
        <input type="text" name="template_name" class="form-control" value="{{ old('template_name', optional($template)->template_name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Project Type ID</label>
        <input type="number" name="project_type_id" class="form-control" value="{{ old('project_type_id', optional($template)->project_type_id ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Project Category ID</label>
        <input type="number" name="project_category_id" class="form-control" value="{{ old('project_category_id', optional($template)->project_category_id ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Template Key</label>
        <input type="text" name="template_key" class="form-control" value="{{ old('template_key', optional($template)->template_key ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Version</label>
        <input type="number" name="version" class="form-control" value="{{ old('version', optional($template)->version ?? 1) }}">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="template-is-active" @checked(old('is_active', optional($template)->is_active ?? false))>
            <label class="form-check-label" for="template-is-active">Active</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">Cover Page</label>
        <textarea name="cover_page" class="form-control" rows="4">{{ old('cover_page', optional($template)->cover_page ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Template Body</label>
        <textarea name="template_body" class="form-control" rows="10" required>{{ old('template_body', optional($template)->template_body ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Contract Footer</label>
        <textarea name="contract_footer" class="form-control" rows="4">{{ old('contract_footer', optional($template)->contract_footer ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Contract Signature</label>
        <textarea name="contract_signature" class="form-control" rows="4">{{ old('contract_signature', optional($template)->contract_signature ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Template Sections (JSON)</label>
        <textarea name="template_sections" class="form-control" rows="4">{{ old('template_sections', isset($template) && is_array(optional($template)->template_sections) ? json_encode(optional($template)->template_sections, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
    </div>
</div>
