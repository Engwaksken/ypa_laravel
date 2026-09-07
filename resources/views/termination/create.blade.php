@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Terminate Contract</h4>
            <small class="text-muted">Record a contract closure and refund calculation</small>
        </div>
        <a href="{{ route('termination.index') }}" class="btn btn-outline-dark">Back</a>
    </div>

    @if($contract)
        <div class="alert alert-warning">
            Contract selected: <strong>{{ $contract->contract_number }}</strong>
            <span class="ms-2">Outstanding: {{ number_format((float) ($contract->contract_outstanding ?? 0), 2) }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('termination.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Contract</label>
                    <select name="contract_id" class="form-select" required>
                        <option value="">Select contract</option>
                        @foreach($contracts as $item)
                            <option value="{{ $item->id }}" @selected((string) old('contract_id', optional($contract)->id ?? '') === (string) $item->id)>
                                {{ $item->contract_number }} - {{ strtolower((string) $item->contract_for) === 'group' ? optional($item->group)->group_name : optional($item->member)->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Termination Date</label>
                    <input type="date" name="termination_date" value="{{ old('termination_date', now()->format('Y-m-d')) }}" class="form-control" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount Paid (from contract)</label>
                    <input type="text" class="form-control" value="{{ number_format((float) (optional($contract)->amount_paid ?? 0), 2) }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Project Projection</label>
                    <input type="number" step="0.01" min="0" name="project_projection" value="{{ old('project_projection') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Deduction Rate %</label>
                    <input type="number" step="0.0001" min="0" max="100" name="deduction_rate" value="{{ old('deduction_rate', 50) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Deduction Amount</label>
                    <input type="number" step="0.01" min="0" name="deduction_amount" value="{{ old('deduction_amount') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Refund Amount</label>
                    <input type="number" step="0.01" min="0" name="refund_amount" value="{{ old('refund_amount') }}" class="form-control">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="4" class="form-control">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-danger">Save Termination</button>
                    <a href="{{ route('termination.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
