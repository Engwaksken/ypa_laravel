@extends('layouts.app')
@section('title', 'Financial Report')
@section('content')
@php
    $money = fn ($amount) => 'UGX ' . number_format((float) ($amount ?? 0), 2);
    $tabLabels = ['overview' => 'Overview', 'income' => 'Transaction income', 'projects' => 'Projects', 'branches' => 'Branches', 'ledger' => 'General ledger', 'trial' => 'Trial balance', 'balance' => 'Balance sheet', 'cashflow' => 'Cash flow', 'ledger-income' => 'Ledger income'];
    $q = fn (array $extra) => route('financial-reports.index', array_merge(
        array_filter(['from' => $from, 'to' => $to, 'branch' => $branchId > 0 ? $branchId : null, 'tab' => $tab]),
        $extra
    ));
@endphp
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <div class="dash-greeting">Reports</div>
            <h1 class="dash-name">Financial Report</h1>
            <div class="dash-date">
                {{ $branchId === 0 ? 'All Branches' : $branchId }} &middot;
                {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}
            </div>
        </div>
        @if(app(\App\Services\PermissionService::class)->can('export_financial_reports'))
        <a href="{{ route('financial-reports.export', ['from' => $from, 'to' => $to, 'branch' => $branchId, 'tab' => $tab]) }}" class="btn btn-outline-secondary">
            <i class="fas fa-file-csv"></i> Export {{ $tabLabels[$tab] ?? ucfirst($tab) }}
        </a>
        @endif
    </div>

    <div class="dash-panel mb-4">
        <div class="dash-panel-head"><span><i class="fas fa-filter"></i> Filters</span></div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('financial-reports.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-md-3">
                    <label for="financialFrom" class="form-label">From</label>
                    <input type="date" id="financialFrom" name="from" value="{{ $from }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="financialTo" class="form-label">To</label>
                    <input type="date" id="financialTo" name="to" value="{{ $to }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="financialBranch" class="form-label">Branch</label>
                    <select id="financialBranch" name="branch" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId === (int) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                    <a href="{{ route('financial-reports.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <ul class="nav nav-pills mb-4" aria-label="Financial report sections">
        @foreach(\App\Http\Controllers\FinancialReportController::TABS as $t)
            <li class="nav-item">
                <a class="nav-link {{ $tab === $t ? 'active' : '' }}" href="{{ $q(['tab' => $t]) }}" @if($tab === $t) aria-current="page" @endif>
                    {{ $tabLabels[$t] ?? ucfirst($t) }}
                </a>
            </li>
        @endforeach
    </ul>

    @if($tab === 'overview')
        <div class="dash-grid d-grid-5 mb-4">
            <div class="dash-card">
                <div class="dash-card-top"><span class="dash-card-title">Revenue</span><span class="dash-card-icon"><i class="fas fa-money-bill-wave"></i></span></div>
                <div class="dash-card-value">{{ $money($data['revenue']) }}</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-top"><span class="dash-card-title">Expenses</span><span class="dash-card-icon"><i class="fas fa-receipt"></i></span></div>
                <div class="dash-card-value text-danger">{{ $money($data['expenses']) }}</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-top"><span class="dash-card-title">Harvest Payouts</span><span class="dash-card-icon"><i class="fas fa-seedling"></i></span></div>
                <div class="dash-card-value text-warning">{{ $money($data['harvests']) }}</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-top"><span class="dash-card-title">Projects</span><span class="dash-card-icon"><i class="fas fa-project-diagram"></i></span></div>
                <div class="dash-card-value text-info">{{ $money($data['projects']) }}</div>
            </div>
            <div class="dash-card {{ $data['net'] >= 0 ? 'border-success' : 'border-danger' }}">
                <div class="dash-card-top"><span class="dash-card-title">Net Position</span><span class="dash-card-icon"><i class="fas fa-chart-line"></i></span></div>
                <div class="dash-card-value {{ $data['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($data['net']) }}</div>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head"><span><i class="fas fa-table"></i> Summary Table</span></div>
            <div class="dash-panel-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            @foreach([
                                'Total Revenue (Collected)' => $data['revenue'],
                                'Manual Expenses' => -$data['expenses'],
                                'Harvest Payouts' => -$data['harvests'],
                                'Project Revenue' => $data['projects'],
                                'Net Position' => $data['net'],
                            ] as $label => $value)
                                <tr class="{{ $label === 'Net Position' ? 'fw-bold' : '' }}">
                                    <td>{{ $label }}</td>
                                    <td class="text-end {{ $value >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($value) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($tab === 'income')
        <div class="dash-panel">
            <div class="dash-panel-head"><span><i class="fas fa-money-bill-wave"></i> Approved Transactions</span></div>
            <div class="dash-panel-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Date</th><th>Reference</th><th>Payer Type</th><th>Method</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            @forelse($data['transactions'] as $tx)
                                <tr>
                                    <td>{{ $tx['date'] }}</td>
                                    <td class="fw-bold">{{ $tx['order_number'] }}</td>
                                    <td>{{ $tx['customer'] }}</td>
                                    <td>{{ $tx['method'] }}</td>
                                    <td><span class="badge text-bg-{{ $tx['status'] === 'completed' ? 'success' : 'warning' }}">{{ $tx['status'] }}</span></td>
                                    <td class="text-end fw-bold text-success">{{ $money($tx['amount']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No approved transactions in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($tab === 'projects')
        <div class="dash-grid d-grid-4 mb-4">
            <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Project Revenue</span><span class="dash-card-icon"><i class="fas fa-coins"></i></span></div><div class="dash-card-value">{{ $money($data['projectsDetail']->sum('revenue')) }}</div></div>
            <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Project Cost</span><span class="dash-card-icon"><i class="fas fa-truck"></i></span></div><div class="dash-card-value text-danger">{{ $money($data['projectsDetail']->sum('cost')) }}</div></div>
            <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Project Profit</span><span class="dash-card-icon"><i class="fas fa-chart-line"></i></span></div><div class="dash-card-value text-success">{{ $money($data['projectsDetail']->sum('profit')) }}</div></div>
            <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Avg Margin</span><span class="dash-card-icon"><i class="fas fa-percent"></i></span></div><div class="dash-card-value">{{ number_format($data['projectsDetail']->avg('margin') ?? 0, 1) }}%</div></div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head"><span><i class="fas fa-project-diagram"></i> Project Profitability</span></div>
            <div class="dash-panel-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Project</th><th>Branch</th><th class="text-end">Revenue</th><th class="text-end">Cost</th><th class="text-end">Profit</th><th class="text-end">Margin</th></tr></thead>
                        <tbody>
                            @forelse($data['projectsDetail'] as $p)
                                <tr>
                                    <td class="fw-bold">{{ $p['name'] }}</td>
                                    <td>{{ $p['branch'] }}</td>
                                    <td class="text-end">{{ $money($p['revenue']) }}</td>
                                    <td class="text-end">{{ $money($p['cost']) }}</td>
                                    <td class="text-end fw-bold {{ $p['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ $money($p['profit']) }}</td>
                                    <td class="text-end">{{ number_format($p['margin'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No projects in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($tab === 'branches')
        <div class="dash-panel">
            <div class="dash-panel-head"><span><i class="fas fa-building"></i> Branch Financials</span></div>
            <div class="dash-panel-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Branch</th><th class="text-end">Revenue</th><th class="text-end">Expenses</th><th class="text-end">Harvest Payouts</th><th class="text-end">Net</th></tr></thead>
                        <tbody>
                            @forelse($data['branchesDetail'] as $b)
                                <tr>
                                    <td class="fw-bold">{{ $b['name'] }}</td>
                                    <td class="text-end text-success">{{ $money($b['revenue']) }}</td>
                                    <td class="text-end text-danger">{{ $money($b['expenses']) }}</td>
                                    <td class="text-end text-warning">{{ $money($b['harvests']) }}</td>
                                    <td class="text-end fw-bold {{ $b['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($b['net']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No branch data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @else
        @include('reports.financial.statement', ['statement' => $data['statement']])
    @endif

</div>
@endsection
