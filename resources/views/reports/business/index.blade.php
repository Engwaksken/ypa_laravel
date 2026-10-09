@extends('layouts.app')
@section('title', 'Business Report')
@section('content')
@php
    $money = fn ($amount) => 'UGX ' . number_format((float) ($amount ?? 0));
    $q = fn (array $extra) => route('business-report.index', array_merge(
        array_filter(['from' => $from, 'to' => $to, 'branch' => $branchId > 0 ? $branchId : null]),
        $extra
    ));
@endphp
<div class="dash-wrap">
    <div class="alert alert-info" role="note">Sales and profit figures on this report use migrated orders. Point-of-sale invoices are shown separately in Sales Report; sources are kept separate until historical overlaps are reconciled.</div>

    <div class="dash-head">
        <div>
            <div class="dash-greeting">Reports</div>
            <h1 class="dash-name">Business Statement</h1>
            <div class="dash-date">
                {{ $branchId === 0 ? 'All Branches' : $branchId }} &middot;
                {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(app(\App\Services\PermissionService::class)->can('export_business_report'))
            <a href="{{ route('business-report.export', ['from' => $from, 'to' => $to, 'branch' => $branchId, 'section' => $section]) }}" class="btn btn-outline-secondary">
                <i class="fas fa-file-csv"></i> Export {{ ucfirst($section) }}
            </a>
            @endif
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="dash-panel mb-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Report Filters</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('business-report.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="section" value="{{ $section }}">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId === (int) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Section</label>
                    <select name="jump_to" class="form-select" onchange="if(this.value) window.location.href=this.value;">
                        <option value="">Go to section...</option>
                        @foreach(\App\Http\Controllers\BusinessReportController::SECTIONS as $key)
                            <option value="{{ $q(['section' => $key]) }}" @selected($section === $key)>{{ ucfirst($key) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                    <a href="{{ route('business-report.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-grid d-grid-4 mb-4">
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Sales</span><span class="dash-card-icon"><i class="fas fa-shopping-cart"></i></span></div>
            <div class="dash-card-value">{{ number_format($data['sales_count']) }}</div>
            <div class="dash-card-sub">{{ $money($data['revenue_collected']) }} collected</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Pending</span><span class="dash-card-icon"><i class="fas fa-clock"></i></span></div>
            <div class="dash-card-value">{{ $money($data['pending_amount']) }}</div>
            <div class="dash-card-sub">{{ number_format($data['active_customers']) }} active customers</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Stock Value</span><span class="dash-card-icon"><i class="fas fa-boxes"></i></span></div>
            <div class="dash-card-value">{{ $money($data['total_stock_value']) }}</div>
            <div class="dash-card-sub">{{ number_format($data['total_stock_qty']) }} units on hand</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Expenses</span><span class="dash-card-icon"><i class="fas fa-receipt"></i></span></div>
            <div class="dash-card-value">{{ $money($data['total_expenses']) }}</div>
            <div class="dash-card-sub">{{ $money($data['harvest_payouts']) }} harvest payouts</div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(\App\Http\Controllers\BusinessReportController::SECTIONS as $key)
            <a href="{{ $q(['section' => $key]) }}" class="btn btn-sm {{ $section === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ ucfirst($key) }}
            </a>
        @endforeach
    </div>

    @include('reports.business.sections.' . $section, [
        'from' => $from,
        'to' => $to,
        'branchId' => $branchId,
        'data' => $data,
        'sectionData' => $sections[$section] ?? [],
        'money' => $money,
    ])

</div>
@endsection
