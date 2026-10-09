@extends('layouts.app')

@section('title', 'Contract Templates')

@section('content')
@php($canEditTemplates = app(\App\Services\PermissionService::class)->can('contracts_edit'))
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Contract Templates</h1>
            <div class="dash-date">HTML templates used to render contracts</div>
        </div>
        @if($canEditTemplates)
            <button type="button" class="btn btn-primary" onclick="openContractTemplateModal()">
                <i class="fas fa-plus"></i> Add Template
            </button>
        @endif
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-file-contract"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Active</span>
                <span class="dash-card-icon"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="dash-card accent-muted">
            <div class="dash-card-top">
                <span class="dash-card-title">Inactive</span>
                <span class="dash-card-icon"><i class="fas fa-circle-pause"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['inactive']) }}</div>
        </div>
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Updated (30 days)</span>
                <span class="dash-card-icon"><i class="fas fa-clock-rotate-left"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['recent']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Template List</span>
            <span class="text-muted small">{{ $templates->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Key</th>
                            <th class="num">Version</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td><strong>{{ $template->template_name }}</strong></td>
                                <td>{{ $template->template_key ?: '-' }}</td>
                                <td class="num">{{ $template->version }}</td>
                                <td><span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>{{ optional($template->updated_at)->format('Y-m-d') ?? '-' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('contract-templates.preview', $template) }}" class="btn btn-sm btn-outline-info" title="Preview" aria-label="Preview template {{ $template->template_name }}"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></a>
                                    <a href="{{ route('contract-templates.show', $template) }}" class="btn btn-sm btn-outline-primary" title="View" aria-label="View template {{ $template->template_name }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                    @if($canEditTemplates)
                                        @include('contract_templates._edit-button', ['template' => $template, 'buttonClass' => 'btn btn-sm btn-outline-secondary', 'label' => ''])
                                        <form action="{{ route('contract-templates.destroy', $template) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete template?" data-confirm-message="Delete {{ $template->template_name }}? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete template {{ $template->template_name }}"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="6"><i class="fas fa-inbox"></i>No templates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $templates->links() }}</div>
    </div>

</div>

@if($canEditTemplates)
    @include('contract_templates._modal')
@endif
@endsection
