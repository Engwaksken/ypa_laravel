@extends('layouts.app')

@section('title', e($group->group_name))

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canEdit = $permission->can('groups_edit');
    $canDelete = $permission->can('groups_delete');
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $group->group_name }}</h1>
            <div class="dash-date">
                {{ $group->group_code }} &middot; {{ $group->group_category }}
                <span class="badge bg-{{ $group->status_badge }} ms-2">{{ $group->status }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($canEdit)
                <a href="{{ route('groups.edit', $group) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endif
            <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-circle-info"></i> Group Information</span>
                </div>
                <div class="dash-panel-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted">Group Code</th>
                                <td>{{ $group->group_code }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Category</th>
                                <td>{{ $group->group_category }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Country</th>
                                <td>{{ $group->country ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Sub-region</th>
                                <td>{{ $group->uganda_subregion ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">District</th>
                                <td>{{ $group->uganda_district ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Formation Date</th>
                                <td>{{ $group->formation_date ? $group->formation_date->format('M d, Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Branch</th>
                                <td>{{ $group->branch->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Mobilizer</th>
                                <td>{{ $group->mobilizer->full_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Status</th>
                                <td><span class="badge bg-{{ $group->status_badge }}">{{ $group->status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dash-panel mt-4">
                <div class="dash-panel-head">
                    <span><i class="fas fa-building-columns"></i> Bank Details</span>
                </div>
                <div class="dash-panel-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted">Bank Name</th>
                                <td>{{ $group->bank_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Account Name</th>
                                <td>{{ $group->bank_account_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Account Number</th>
                                <td>{{ $group->bank_account_number ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-user-group"></i> Members ({{ $group->members->count() }})</span>
                </div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Membership ID</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group->members as $gm)
                                    <tr>
                                        <td>{{ $gm->member->membership_id ?? '-' }}</td>
                                        <td>{{ $gm->member->full_name ?? '-' }}</td>
                                        <td>{{ $gm->member->telephone1 ?? '-' }}</td>
                                        <td>{{ $gm->role ?? 'Member' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No members in this group yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="dash-panel mt-4">
                <div class="dash-panel-head">
                    <span><i class="fas fa-user-tie"></i> Coordinators ({{ $group->coordinators->count() }})</span>
                </div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group->coordinators as $gc)
                                    <tr>
                                        <td>{{ $gc->coordinator->name ?? '-' }}</td>
                                        <td>{{ $gc->role ?? 'Coordinator' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No coordinators assigned.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($group->nonMemberCoordinators->isNotEmpty())
                <div class="dash-panel mt-4">
                    <div class="dash-panel-head">
                        <span><i class="fas fa-user-tie"></i> Non-Member Coordinators ({{ $group->nonMemberCoordinators->count() }})</span>
                    </div>
                    <div class="dash-panel-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group->nonMemberCoordinators as $nmc)
                                        <tr>
                                            <td>{{ $nmc->name ?? '-' }}</td>
                                            <td>{{ $nmc->phone ?? '-' }}</td>
                                            <td>{{ $nmc->email ?? '-' }}</td>
                                            <td>{{ $nmc->role ?? 'Coordinator' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <div class="dash-panel mt-4">
                <div class="dash-panel-head">
                    <span><i class="fas fa-file"></i> Documents ({{ $group->documents->count() }})</span>
                </div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Uploaded</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group->documents as $doc)
                                    <tr>
                                        <td>
                                            @if($doc->file_path)
                                                <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank">
                                                    <i class="fas fa-file"></i> {{ $doc->document_name ?? basename($doc->file_path) }}
                                                </a>
                                            @else
                                                {{ $doc->document_name ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{{ $doc->created_at ? $doc->created_at->format('M d, Y') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No documents uploaded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection