@extends('layouts.app')

@section('title', 'Members')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $money = fn ($amount) => 'UGX ' . number_format((float) $amount, 0);
    $canRegister = $permission->can('members_register');
    $canEdit = $permission->can('members_edit');
    $canDelete = $permission->can('members_delete');
    $canExport = $permission->can('members_export');
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Members</h1>
            <div class="dash-date">{{ number_format($kpi['total']) }} registered member(s)</div>
        </div>
        <div class="d-flex gap-2">
            @if($canExport)
                <a href="{{ route('members.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            @endif
            @if($canRegister)
                <button type="button" class="btn btn-primary" onclick="openMemberModal()">
                    <i class="fas fa-user-plus"></i> Register Member
                </button>
            @endif
        </div>
    </div>

    <div class="dash-grid d-grid-4 mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Total Members</span>
                <span class="dash-card-icon"><i class="fas fa-users"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['total']) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Active</span>
                <span class="dash-card-icon"><i class="fas fa-user-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['active']) }}</div>
        </div>
        <div class="dash-card accent-warning">
            <div class="dash-card-top">
                <span class="dash-card-title">Pending</span>
                <span class="dash-card-icon"><i class="fas fa-clock"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['pending']) }}</div>
        </div>
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Total Collected</span>
                <span class="dash-card-icon"><i class="fas fa-sack-dollar"></i></span>
            </div>
            <div class="dash-card-value">{{ $money($kpi['tp']) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Fully Paid</span>
                <span class="dash-card-icon"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['paid']) }}</div>
        </div>
        <div class="dash-card accent-warning">
            <div class="dash-card-top">
                <span class="dash-card-title">Partially Paid</span>
                <span class="dash-card-icon"><i class="fas fa-circle-half-stroke"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['partial']) }}</div>
        </div>
        <div class="dash-card accent-muted">
            <div class="dash-card-top">
                <span class="dash-card-title">Unpaid</span>
                <span class="dash-card-icon"><i class="fas fa-circle-xmark"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['unpaid']) }}</div>
        </div>
        <div class="dash-card accent-danger">
            <div class="dash-card-top">
                <span class="dash-card-title">Outstanding</span>
                <span class="dash-card-icon"><i class="fas fa-hourglass-half"></i></span>
            </div>
            <div class="dash-card-value">{{ $money($kpi['to']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Members</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('members.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name, ID, phone, NIN...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['Active', 'Pending', 'Inactive', 'Suspended', 'Terminated'] as $st)
                            <option value="{{ $st }}" @selected($statusFilter === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select">
                        <option value="">All Payments</option>
                        @foreach(['PAID', 'PARTIAL', 'UNPAID'] as $ps)
                            <option value="{{ $ps }}" @selected($paymentFilter === $ps)>{{ ucfirst(strtolower($ps)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Member List</span>
            <span class="text-muted small">{{ $members->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Membership ID</th>
                            <th>Name</th>
                            <th>Branch</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td><strong>{{ $member->membership_id }}</strong></td>
                                <td><a href="{{ route('members.show', $member) }}" class="text-decoration-none">{{ $member->full_name }}</a></td>
                                <td>{{ $member->branch->name ?? '-' }}</td>
                                <td>{{ $member->telephone1 ?? '-' }}</td>
                                <td>{{ $member->email ?? '-' }}</td>
                                <td>
                                    @php
                                        $badge = match($member->membership_status) {
                                            'Active' => 'success',
                                            'Pending' => 'warning',
                                            'Inactive' => 'secondary',
                                            'Suspended' => 'danger',
                                            'Terminated' => 'dark',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ $member->membership_status ?? '-' }}</span>
                                </td>
                                <td>
                                    @php
                                        $ps = 'UNPAID';
                                        if (\Illuminate\Support\Facades\Schema::hasTable('payment_transactions')) {
                                            $row = \Illuminate\Support\Facades\DB::table('payment_transactions')
                                                ->where('member_id', $member->id)
                                                ->selectRaw('COALESCE(SUM(total_amount_paid),0) as paid, COALESCE(SUM(total_amount_outstanding),0) as out')
                                                ->first();
                                            $paid = (float) ($row->paid ?? 0);
                                            $out = (float) ($row->out ?? 0);
                                            $ps = $paid <= 0 ? 'UNPAID' : ($out <= 0.009 ? 'PAID' : 'PARTIAL');
                                        }
                                        $pBadge = match($ps) { 'PAID' => 'success', 'PARTIAL' => 'warning', default => 'secondary' };
                                    @endphp
                                    <span class="badge bg-{{ $pBadge }}">{{ $ps }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('members.show', $member) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                    @if($canEdit)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                            data-url="{{ route('members.update', $member) }}"
                                            data-record='@json($memberRecords[$member->id] ?? [])'
                                            onclick="openMemberEdit(this)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif
                                    @if($canDelete)
                                        <form action="{{ route('members.destroy', $member) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete member?" data-confirm-message="Delete {{ $member->full_name }} ({{ $member->membership_id }})? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="8"><i class="fas fa-inbox"></i>No members found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">
            {{ $members->links() }}
        </div>
    </div>

</div>

@if($canRegister || $canEdit)
    @include('members._modal_form')
@endif
@endsection
