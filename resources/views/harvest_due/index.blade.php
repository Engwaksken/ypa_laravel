@extends('layouts.app')

@section('title', 'Harvest Due')

@section('content')
@php($canRecord = app(\App\Services\PermissionService::class)->can('harvest_due_full'))
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Harvest Due</h1>
            <div class="dash-date">Contract items with harvestable balances &middot; {{ number_format($items->total()) }} item(s)</div>
        </div>
        <a href="{{ route('harvests.index') }}" class="btn btn-outline-secondary"><i class="fas fa-seedling"></i> Harvests</a>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Items</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('harvest-due.index') }}" class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Contract, owner, item, project...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach([10,25,50,100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('harvest-due.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-calendar-check"></i> Due Items</span>
            <span class="text-muted small">{{ $items->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Contract</th>
                            <th>Owner</th>
                            <th>Item</th>
                            <th>Project</th>
                            <th class="num">Balance Amount</th>
                            <th class="num">Balance Quantity</th>
                            <th>Next Due</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $contract = $item->contract;
                                $owner = strtolower((string) optional($contract)->contract_for) === 'group' ? optional(optional($contract)->group)->group_name : optional(optional($contract)->member)->full_name;
                                $amount = (float) ($item->balance_amount ?? $item->projected_harvest_balance ?? $item->harvest_amount ?? 0);
                                $quantity = (float) ($item->balance_quantity ?? $item->harvest_quantity ?? 0);
                            @endphp
                            <tr>
                                <td><strong>{{ optional($contract)->contract_number ?? '-' }}</strong></td>
                                <td>{{ $owner ?: '-' }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->project_name ?: optional(optional($contract)->project)->project_name }}</td>
                                <td class="num">{{ number_format($amount, 2) }}</td>
                                <td class="num">{{ number_format($quantity, 2) }}</td>
                                <td>{{ $item->next_due_date ?: '-' }}</td>
                                <td class="text-end">
                                    @if($canRecord)
                                        <button type="button" class="btn btn-sm btn-outline-success" title="Record harvest" aria-label="Record harvest for {{ $item->item_name }}"
                                            data-url="{{ route('harvest-due.record', $item) }}"
                                            data-label="{{ optional($contract)->contract_number ?? '-' }} &middot; {{ $item->item_name }}"
                                            data-amount="{{ $amount > 0 ? $amount : '' }}"
                                            data-quantity="{{ $quantity > 0 ? $quantity : '' }}"
                                            onclick="openHarvestDueModal(this)">
                                            <i class="fas fa-seedling" aria-hidden="true"></i>
                                        </button>
                                    @else
                                        <span class="text-muted small">No access</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="8"><i class="fas fa-inbox"></i>No harvest-due items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $items->links() }}</div>
    </div>

</div>

@if($canRecord)
<div class="modal fade" id="harvestDueModal" tabindex="-1" aria-labelledby="harvestDueModalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="harvestDueForm" method="POST" action="{{ route('harvest-due.index') }}">
                @csrf
                <input type="hidden" name="_method" id="harvestDueMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="harvestDueModalTitle">Add Harvest</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="harvestDueLabel"></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="harvestDueType">Harvest Type <span class="text-danger">*</span></label>
                            <select name="harvest_type" id="harvestDueType" class="form-select" required>
                                @foreach(['Cash','Bags','Goats','Monthly Payout','Profit'] as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="harvestDueDate">Harvest Date <span class="text-danger">*</span></label>
                            <input type="date" name="harvest_date" id="harvestDueDate" value="{{ now()->format('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="harvestDueAmount">Amount</label>
                            <input type="number" step="0.01" min="0" name="amount_harvested" id="harvestDueAmount" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="harvestDueQuantity">Quantity</label>
                            <input type="number" step="0.01" min="0" name="quantity_harvested" id="harvestDueQuantity" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="harvestDuePeriods">Periods</label>
                            <input type="number" step="1" min="1" name="periods_due" id="harvestDuePeriods" value="1" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openHarvestDueModal(btn) {
        var form = document.getElementById('harvestDueForm');
        form.reset();
        form.action = btn.dataset.url;
        document.getElementById('harvestDueMethod').value = 'POST';
        document.getElementById('harvestDueModalTitle').textContent = 'Add Harvest';
        document.getElementById('harvestDueLabel').textContent = btn.dataset.label || '';
        form.dataset.modalContext = JSON.stringify({label: btn.dataset.label || ''});
        document.getElementById('harvestDueDate').value = "{{ now()->format('Y-m-d') }}";
        document.getElementById('harvestDueAmount').value = btn.dataset.amount || '';
        document.getElementById('harvestDueQuantity').value = btn.dataset.quantity || '';
        document.getElementById('harvestDuePeriods').value = 1;
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('harvestDueModal')).show();
        }
    }
    document.getElementById('harvestDueForm')?.addEventListener('ypa:modal-restoring', function (event) {
        var trigger = [...document.querySelectorAll('[data-url][onclick="openHarvestDueModal(this)"]')].find(function (button) {
            try { return new URL(button.dataset.url, location.href).href === event.detail.action; } catch { return false; }
        });
        var label = trigger ? trigger.dataset.label : event.detail.context.label;
        document.getElementById('harvestDueLabel').textContent = typeof label === 'string' ? label : 'Previously submitted harvest item';
        // Do not call openHarvestDueModal: it would reset amount, quantity and periods.
    });
</script>
@endpush
@endif
@endsection
