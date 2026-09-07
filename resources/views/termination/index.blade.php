@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Contract Termination</h4>
            <small class="text-muted">Closed contract records</small>
        </div>
        @if(app(\App\Services\PermissionService::class)->can('termination'))
            <a href="{{ route('termination.create') }}" class="btn btn-danger">Terminate Contract</a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search contract number, party, reason...">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Contract</th>
                        <th>Party</th>
                        <th>Reason</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Deduction</th>
                        <th class="text-end">Refund</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($terminations as $termination)
                        <tr>
                            <td>{{ optional($termination->termination_date)->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ $termination->contract->contract_number ?? '-' }}</td>
                            <td>{{ strtolower((string) optional($termination->contract)->contract_for) === 'group' ? optional($termination->contract->group)->group_name : optional(optional($termination->contract)->member)->full_name }}</td>
                            <td>{{ $termination->reason }}</td>
                            <td class="text-end">{{ number_format((float) $termination->amount_paid, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $termination->deduction_amount, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $termination->refund_amount, 2) }}</td>
                            <td><a href="{{ route('termination.show', $termination) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No termination records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $terminations->links() }}</div>
    </div>
</div>
@endsection
