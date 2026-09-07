@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">{{ $template->template_name }}</h4>
            <small class="text-muted">Template key: {{ $template->template_key }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contract-templates.preview', $template) }}" class="btn btn-outline-secondary">Preview</a>
            @if(app(\App\Services\PermissionService::class)->can('contracts_edit'))
                <a href="{{ route('contract-templates.edit', $template) }}" class="btn btn-outline-warning">Edit</a>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('contracts_edit'))
                <form method="POST" action="{{ route('contract-templates.destroy', $template) }}" onsubmit="return confirm('Delete this template?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            @endif
            <a href="{{ route('contract-templates.index') }}" class="btn btn-outline-dark">Back</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1"><strong>Version:</strong> {{ $template->version }}</p>
            <p class="mb-1"><strong>Status:</strong> {{ $template->is_active ? 'Active' : 'Inactive' }}</p>
            <p class="mb-1"><strong>Project Type:</strong> {{ $template->project_type_id ?? '-' }}</p>
            <p class="mb-1"><strong>Project Category:</strong> {{ $template->project_category_id ?? '-' }}</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Template Body</strong></div>
        <div class="card-body">
            <pre class="mb-0" style="white-space: pre-wrap;">{{ $template->template_body }}</pre>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Template Sections</strong></div>
        <div class="card-body">
            <pre class="mb-0">{{ json_encode($template->template_sections, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
</div>
@endsection
