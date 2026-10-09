{{-- Shared payment fields used by the index modal and the standalone create page. --}}
@php($contract = $contract ?? null)
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Contract <span class="text-danger">*</span></label>
        <select name="contract_id" id="paymentContract" class="form-select" required>
            <option value="">Select contract</option>
            @foreach($contracts as $item)
                <option value="{{ $item->id }}" data-outstanding="{{ number_format((float) ($item->contract_outstanding ?? 0), 2, '.', '') }}" @selected((string) old('contract_id', optional($contract)->id ?? '') === (string) $item->id)>
                    {{ $item->contract_number }}
                    @if($item->member || $item->group)
                        - {{ strtolower((string) $item->contract_for) === 'group' ? optional($item->group)->group_name : optional($item->member)->full_name }}
                    @endif
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
        <select name="payment_method_id" id="paymentMethod" class="form-select" required>
            <option value="">Select payment method</option>
            @foreach($paymentMethods as $method)
                <option value="{{ $method->id }}" @selected((string) old('payment_method_id') === (string) $method->id)>{{ $method->method_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Amount <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0.01" name="amount" id="paymentAmount" value="{{ old('amount', optional($contract)->contract_outstanding ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Payment Date</label>
        <input type="date" name="payment_date" id="paymentDate" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Reference</label>
        <input type="text" name="reference" id="paymentReference" value="{{ old('reference') }}" class="form-control" placeholder="Optional reference">
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" id="paymentNotes" rows="3" class="form-control">{{ old('notes') }}</textarea>
    </div>
</div>
