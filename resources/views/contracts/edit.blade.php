@extends('layouts.app')

@section('title', 'Edit Contract')

@section('content')
<div class="dash-wrap">
    <div class="dash-head">
        <div>
            <h1 class="dash-name">Edit Contract</h1>
            <div class="dash-date">{{ $contract->contract_number }}</div>
        </div>
        <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form method="POST" action="{{ route('contracts.update', $contract) }}">
        @method('PUT')
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-file-contract"></i> Contract Details</span>
            </div>
            <div class="dash-panel-body">
                @include('contracts._form')
            </div>
            <div class="dash-panel-foot d-flex justify-content-end gap-2">
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Update Contract</button>
            </div>
        </div>
    </form>
</div>
@endsection
