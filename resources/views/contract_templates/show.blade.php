@extends('layouts.app')

@section('title', $template->template_name)

@section('content')
@php($canEditTemplates = app(\App\Services\PermissionService::class)->can('contracts_edit'))
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $template->template_name }}</h1>
            <div class="dash-date"><a href="{{ route('contract-templates.index') }}">Contract Templates</a> / Key: {{ $template->template_key ?: '-' }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('contract-templates.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
            <a href="{{ route('contract-templates.preview', $template) }}" class="btn btn-outline-info"><i class="fas fa-magnifying-glass me-1"></i> Preview</a>
            @if($canEditTemplates)
                @include('contract_templates._edit-button', ['template' => $template, 'buttonClass' => 'btn btn-outline-primary', 'label' => 'Edit'])
                <form method="POST" action="{{ route('contract-templates.destroy', $template) }}" class="ypa-confirm-delete" data-confirm-title="Delete template?" data-confirm-message="Delete {{ $template->template_name }}? This cannot be undone.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit"><i class="fas fa-trash me-1"></i> Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-circle-info"></i> Details</span>
        </div>
        <div class="dash-panel-body p-0">
            <table class="table align-middle mb-0">
                <tbody>
                    <tr><th class="w-25">Version</th><td>{{ $template->version }}</td></tr>
                    <tr><th>Status</th><td><span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
                    <tr><th>Project Type</th><td>{{ $template->project_type_id ?? '-' }}</td></tr>
                    <tr><th>Project Category</th><td>{{ optional($projectCategories->firstWhere('id', $template->project_category_id))->category_name ?? ($template->project_category_id ?? '-') }}</td></tr>
                    <tr><th>Created By</th><td>{{ optional($template->creator)->name ?? '-' }}</td></tr>
                    <tr><th>Last Updated</th><td>{{ optional($template->updated_at)->format('Y-m-d H:i') ?? '-' }}{{ $template->updater ? ' by ' . $template->updater->name : '' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-file-code"></i> Template Body</span>
        </div>
        <div class="dash-panel-body">
            <pre class="preformatted-content mb-0">{{ $template->template_body }}</pre>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-layer-group"></i> Template Sections</span>
        </div>
        <div class="dash-panel-body">
            <pre class="mb-0">{{ json_encode($template->template_sections, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>

</div>

@if($canEditTemplates)
    @include('contract_templates._modal')
@endif
@endsection
