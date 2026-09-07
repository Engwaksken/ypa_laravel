@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Contract Templates</h4>
            <small class="text-muted">HTML templates used to render contracts</small>
        </div>
        @if(app(\App\Services\PermissionService::class)->can('contracts_edit'))
            <a href="{{ route('contract-templates.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Template</a>
        @endif
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->template_name }}</td>
                            <td>{{ $template->template_key }}</td>
                            <td>{{ $template->version }}</td>
                            <td>{{ $template->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('contract-templates.preview', $template) }}" class="btn btn-sm btn-outline-secondary">Preview</a>
                                <a href="{{ route('contract-templates.show', $template) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @if(app(\App\Services\PermissionService::class)->can('contracts_edit'))
                                    <a href="{{ route('contract-templates.edit', $template) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No templates found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $templates->links() }}</div>
</div>
@endsection
