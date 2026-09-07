@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Harvest Due</h4>
            <small class="text-muted">Contract items with harvestable balances</small>
        </div>
        <a href="{{ route('harvests.index') }}" class="btn btn-outline-dark">Harvests</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Contract, owner, item, project...">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach([10,25,50,100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit">Go</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Contract</th>
                        <th>Owner</th>
                        <th>Item</th>
                        <th>Project</th>
                        <th class="text-end">Balance Amount</th>
                        <th class="text-end">Balance Quantity</th>
                        <th>Next Due</th>
                        <th>Record</th>
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
                            <td>{{ optional($contract)->contract_number ?? '-' }}</td>
                            <td>{{ $owner ?: '-' }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->project_name ?: optional(optional($contract)->project)->project_name }}</td>
                            <td class="text-end">{{ number_format($amount, 2) }}</td>
                            <td class="text-end">{{ number_format($quantity, 2) }}</td>
                            <td>{{ $item->next_due_date ?: '-' }}</td>
                            <td>
                                @if(app(\App\Services\PermissionService::class)->can('harvest_due_full'))
                                    <form method="POST" action="{{ route('harvest-due.record', $item) }}" class="row g-1 align-items-end">
                                        @csrf
                                        <div class="col-12">
                                            <select name="harvest_type" class="form-select form-select-sm" required>
                                                @foreach(['Cash','Bags','Goats','Monthly Payout','Profit'] as $type)
                                                    <option value="{{ $type }}">{{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6"><input type="date" name="harvest_date" value="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm" required></div>
                                        <div class="col-6"><input type="number" step="0.01" min="0" name="amount_harvested" value="{{ $amount > 0 ? $amount : '' }}" class="form-control form-control-sm" placeholder="Amount"></div>
                                        <div class="col-6"><input type="number" step="0.01" min="0" name="quantity_harvested" value="{{ $quantity > 0 ? $quantity : '' }}" class="form-control form-control-sm" placeholder="Qty"></div>
                                        <div class="col-6"><input type="number" step="1" min="1" name="periods_due" value="1" class="form-control form-control-sm" placeholder="Periods"></div>
                                        <div class="col-12"><button class="btn btn-sm btn-success w-100" type="submit">Record</button></div>
                                    </form>
                                @else
                                    <span class="text-muted">No access</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No harvest-due items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $items->links() }}</div>
    </div>
</div>
@endsection
