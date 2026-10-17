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
                @if($requiresAmendment ?? false)
                    <div class="alert alert-warning d-flex gap-2 text-start" role="note">
                        <i class="fas fa-file-signature mt-1" aria-hidden="true"></i>
                        <div>
                            <strong>This contract is {{ strtolower(str_replace('_', ' ', (string) ($contract->workflow_status ?: $contract->status))) }}.</strong>
                            Saving records an amendment: your name, the reason below and every changed value are kept in the contract's amendment history. Signatures and approval progress are not reset.
                        </div>
                    </div>
                @endif
                @include('contracts._form')
            </div>
            @if($requiresAmendment ?? false)
                <div class="dash-panel-body border-top">
                    <label class="form-label" for="amendment_reason">Reason for change</label>
                    <textarea id="amendment_reason" name="amendment_reason" class="form-control @error('amendment_reason') is-invalid @enderror" rows="3" minlength="10" maxlength="2000" required
                        placeholder="e.g. Corrected the farmer's bank branch as agreed with the client on 12 Oct"
                        data-hint="Required for signed contracts. Describe what changed and who agreed to it.">{{ old('amendment_reason') }}</textarea>
                    @error('amendment_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endif
            <div class="dash-panel-foot d-flex justify-content-end gap-2">
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> {{ ($requiresAmendment ?? false) ? 'Save Amendment' : 'Update Contract' }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
