@extends('layouts.app')

@section('title', 'Contract Termination')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Contract Termination</h1>
            <div class="dash-date">Closed contract records &middot; {{ number_format($terminations->total()) }} record(s)</div>
        </div>
        @if(app(\App\Services\PermissionService::class)->can('termination'))
            <button type="button" class="btn btn-danger" onclick="openTerminationModal()"><i class="fas fa-ban"></i> Terminate Contract</button>
        @endif
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Terminations</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('termination.index') }}" class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Contract number, party, reason...">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('termination.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Termination Records</span>
            <span class="text-muted small">{{ $terminations->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Contract</th>
                            <th>Party</th>
                            <th>Reason</th>
                            <th class="num">Paid</th>
                            <th class="num">Deduction</th>
                            <th class="num">Refund</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($terminations as $termination)
                            <tr>
                                <td>{{ optional($termination->termination_date)->format('Y-m-d') ?? '-' }}</td>
                                <td><strong>{{ $termination->contract->contract_number ?? '-' }}</strong></td>
                                <td>{{ strtolower((string) optional($termination->contract)->contract_for) === 'group' ? optional($termination->contract->group)->group_name : optional(optional($termination->contract)->member)->full_name }}</td>
                                <td>{{ $termination->reason }}</td>
                                <td class="num">{{ number_format((float) $termination->amount_paid, 2) }}</td>
                                <td class="num">{{ number_format((float) $termination->deduction_amount, 2) }}</td>
                                <td class="num">{{ number_format((float) $termination->refund_amount, 2) }}</td>
                                <td class="text-end"><a href="{{ route('termination.show', $termination) }}" class="btn btn-sm btn-outline-primary" title="View" aria-label="View termination for contract {{ $termination->contract->contract_number ?? 'unknown' }}"><i class="fas fa-eye" aria-hidden="true"></i></a></td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="8"><i class="fas fa-inbox"></i>No termination records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $terminations->links() }}</div>
    </div>

</div>

<div class="modal fade" id="terminationModal" tabindex="-1" aria-labelledby="terminationModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="terminationForm" method="POST" action="{{ route('termination.store') }}">
                @csrf
                <input type="hidden" name="_method" id="terminationFormMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="terminationModalTitle">Add Termination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('termination._fields', ['contract' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-save me-1"></i> Save Termination</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openTerminationModal() {
        var form = document.getElementById('terminationForm');
        form.reset();
        form.action = "{{ route('termination.store') }}";
        document.getElementById('terminationFormMethod').value = 'POST';
        document.getElementById('terminationDate').value = "{{ now()->format('Y-m-d') }}";
        document.getElementById('terminationPaid').value = '0.00';
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('terminationModal')).show();
        }
    }
</script>
@endpush
@endsection
