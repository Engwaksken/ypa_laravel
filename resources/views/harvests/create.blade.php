@extends('layouts.app')

@section('title', 'Record Harvest')

@section('content')
<div class="dash-wrap">
    <div class="dash-head">
        <div>
            <h1 class="dash-name">Record Harvest</h1>
            <div class="dash-date">Create a harvest request for workflow review</div>
        </div>
        <a href="{{ route('harvests.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    @if($contractItem)
        <div class="dash-grid d-grid-2 mb-4">
            <div class="dash-card accent-primary">
                <div class="dash-card-top">
                    <span class="dash-card-title">Item</span>
                    <span class="dash-card-icon"><i class="fas fa-box"></i></span>
                </div>
                <div class="dash-card-value">{{ $contractItem->item_name }}</div>
            </div>
            <div class="dash-card accent-info">
                <div class="dash-card-top">
                    <span class="dash-card-title">Contract</span>
                    <span class="dash-card-icon"><i class="fas fa-file-contract"></i></span>
                </div>
                <div class="dash-card-value">{{ $contractItem->contract->contract_number ?? '-' }}</div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('harvests.store') }}">
        @csrf
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-seedling"></i> Harvest Details</span>
            </div>
            <div class="dash-panel-body">
                @include('harvests._fields')
            </div>
            <div class="dash-panel-foot d-flex justify-content-end gap-2">
                <a href="{{ route('harvests.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> Save Harvest</button>
            </div>
        </div>
    </form>
</div>
@endsection
