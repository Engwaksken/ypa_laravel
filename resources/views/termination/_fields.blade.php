{{-- Shared termination fields used by the index modal and the standalone create page. --}}
@php($contract = $contract ?? null)
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="terminationContract">Contract <span class="text-danger">*</span></label>
        <select name="contract_id" id="terminationContract" class="form-select" required>
            <option value="">Select contract</option>
            @foreach($contracts as $item)
                <option value="{{ $item->id }}" data-paid="{{ number_format((float) ($item->amount_paid ?? 0), 2) }}" @selected((string) old('contract_id', optional($contract)->id ?? '') === (string) $item->id)>
                    {{ $item->contract_number }} - {{ strtolower((string) $item->contract_for) === 'group' ? optional($item->group)->group_name : optional($item->member)->full_name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="terminationDate">Termination Date <span class="text-danger">*</span></label>
        <input type="date" name="termination_date" id="terminationDate" value="{{ old('termination_date', now()->format('Y-m-d')) }}" class="form-control" required>
    </div>
    <div class="col-md-12">
        <label class="form-label" for="terminationReason">Reason <span class="text-danger">*</span></label>
        <input type="text" name="reason" id="terminationReason" value="{{ old('reason') }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="terminationPaid">Amount Paid (from contract)</label>
        <input type="text" id="terminationPaid" class="form-control" value="{{ number_format((float) (optional($contract)->amount_paid ?? 0), 2) }}" disabled>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="terminationProjection">Project Projection</label>
        <input type="number" step="0.01" min="0" name="project_projection" id="terminationProjection" value="{{ old('project_projection') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="terminationDeductionRate">Deduction Rate %</label>
        <input type="number" step="0.0001" min="0" max="100" name="deduction_rate" id="terminationDeductionRate" value="{{ old('deduction_rate', 50) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="terminationDeductionAmount">Deduction Amount</label>
        <input type="number" step="0.01" min="0" name="deduction_amount" id="terminationDeductionAmount" value="{{ old('deduction_amount') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="terminationRefundAmount">Refund Amount</label>
        <input type="number" step="0.01" min="0" name="refund_amount" id="terminationRefundAmount" value="{{ old('refund_amount') }}" class="form-control">
    </div>
    <div class="col-md-12">
        <label class="form-label" for="terminationNotes">Notes</label>
        <textarea name="notes" id="terminationNotes" rows="3" class="form-control">{{ old('notes') }}</textarea>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var select = document.getElementById('terminationContract');
        var paid = document.getElementById('terminationPaid');
        if (!select || !paid) return;
        select.addEventListener('change', function () {
            var option = this.options[this.selectedIndex];
            paid.value = option && option.dataset.paid ? option.dataset.paid : '0.00';
        });
    })();
</script>
@endpush
