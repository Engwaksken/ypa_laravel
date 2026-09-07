@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Record Payment</h4>
            <small class="text-muted">Add a contract payment transaction</small>
        </div>
        <a href="{{ route('payments.index') }}" class="btn btn-outline-dark">Back</a>
    </div>

    @if($contract)
        <div class="alert alert-info mb-3">
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
            <form method="POST" action="{{ route('payments.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Contract</label>
                    <select name="contract_id" class="form-select" required>
                        <option value="">Select contract</option>
                        @foreach($contracts as $item)
                            <option value="{{ $item->id }}" @selected((string) old('contract_id', optional($contract)->id ?? '') === (string) $item->id)>
                                {{ $item->contract_number }}
                                @if($item->member || $item->group)
                                    - {{ strtolower((string) $item->contract_for) === 'group' ? optional($item->group)->group_name : optional($item->member)->full_name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method_id" class="form-select" required>
                        <option value="">Select payment method</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" @selected((string) old('payment_method_id') === (string) $method->id)>{{ $method->method_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', optional($contract)->contract_outstanding ?? '') }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" class="form-control" placeholder="Optional reference">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="4" class="form-control">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save Payment</button>
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
