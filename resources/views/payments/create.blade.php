@extends('layouts.app')

@section('title', 'Record Payment')

@section('content')
<div class="dash-wrap">
    <div class="dash-head">
        <div>
            <h1 class="dash-name">Record Payment</h1>
            <div class="dash-date">Add a contract payment transaction</div>
        </div>
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    @if($contract)
        <div class="dash-grid d-grid-2 mb-4">
            <div class="dash-card accent-primary">
                <div class="dash-card-top">
                    <span class="dash-card-title">Contract</span>
                    <span class="dash-card-icon"><i class="fas fa-file-contract"></i></span>
                </div>
                <div class="dash-card-value">{{ $contract->contract_number }}</div>
            </div>
            <div class="dash-card accent-warning">
                <div class="dash-card-top">
                    <span class="dash-card-title">Outstanding</span>
                    <span class="dash-card-icon"><i class="fas fa-hourglass-half"></i></span>
                </div>
                <div class="dash-card-value">{{ number_format((float) ($contract->contract_outstanding ?? 0), 2) }}</div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('payments.store') }}">
        @csrf
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-money-bill-wave"></i> Payment Details</span>
            </div>
            <div class="dash-panel-body">
                @include('payments._fields')
            </div>
            <div class="dash-panel-foot d-flex justify-content-end gap-2">
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Payment</button>
            </div>
        </div>
    </form>
</div>
@endsection
