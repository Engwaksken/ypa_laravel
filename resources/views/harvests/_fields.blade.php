{{-- Shared harvest fields used by the index modal and the standalone create page. --}}
@php($contractItem = $contractItem ?? null)
<div class="row g-3">
    @if($contractItem)
        <input type="hidden" name="contract_id" value="{{ $contractItem->contract_id }}">
        <input type="hidden" name="contract_item_id" value="{{ $contractItem->id }}">
    @else
        <div class="col-md-12">
            <label class="form-label" for="harvestContract">Contract <span class="text-danger">*</span></label>
            <select name="contract_id" id="harvestContract" class="form-select" required>
                <option value="">Select contract</option>
                @foreach($contracts as $contract)
                    <option value="{{ $contract->id }}" @selected((string) old('contract_id') === (string) $contract->id)>
                        {{ $contract->contract_number }} - {{ strtolower((string) $contract->contract_for) === 'group' ? optional($contract->group)->group_name : optional($contract->member)->full_name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="col-md-4">
        <label class="form-label" for="harvestType">Harvest Type <span class="text-danger">*</span></label>
        <select name="harvest_type" id="harvestType" class="form-select" required>
            @foreach(['Cash','Bags','Goats','Monthly Payout','Profit'] as $type)
                <option value="{{ $type }}" @selected(old('harvest_type', 'Cash') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="harvestDate">Harvest Date <span class="text-danger">*</span></label>
        <input type="date" name="harvest_date" id="harvestDate" value="{{ old('harvest_date', now()->format('Y-m-d')) }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="harvestAmount">Amount</label>
        <input type="number" step="0.01" min="0" name="amount_harvested" id="harvestAmount" value="{{ old('amount_harvested', optional($contractItem)->balance_amount ?? optional($contractItem)->harvest_amount ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="harvestQuantity">Quantity</label>
        <input type="number" step="0.01" min="0" name="quantity_harvested" id="harvestQuantity" value="{{ old('quantity_harvested', optional($contractItem)->balance_quantity ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="harvestGoats">Goats</label>
        <input type="number" step="1" min="0" name="number_of_goats_harvested" id="harvestGoats" value="{{ old('number_of_goats_harvested') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="harvestPeriods">Periods Due</label>
        <input type="number" step="1" min="1" name="periods_due" id="harvestPeriods" value="{{ old('periods_due', 1) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="harvestPaymentMethod">Payment Method</label>
        <input type="text" name="payment_method" id="harvestPaymentMethod" value="{{ old('payment_method') }}" class="form-control" placeholder="Optional">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="harvestPaymentReference">Payment Reference</label>
        <input type="text" name="payment_reference" id="harvestPaymentReference" value="{{ old('payment_reference') }}" class="form-control">
    </div>
    <div class="col-12">
        <label class="form-label" for="harvestNotes">Notes</label>
        <textarea name="notes" id="harvestNotes" rows="3" class="form-control">{{ old('notes') }}</textarea>
    </div>
</div>
