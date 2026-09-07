@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $roleLabel = $permission->roleLabel($role);
    $money = fn ($amount) => 'UGX ' . number_format((float) $amount, 0);
@endphp

<style>
    .inline-form { display: inline; }
</style>

<div class="dash-wrap">

    <div class="dash-head">
        <div class="dash-user">
            <div class="dash-avatar">{{ strtoupper(mb_substr($displayName, 0, 1)) }}</div>
            <div>
                <div class="dash-greeting">{{ $greeting }}</div>
                <h1 class="dash-name">{{ $displayName }}</h1>
                <div class="dash-date">{{ now()->format('l, F j, Y') }}</div>
            </div>
        </div>

        <div class="role-badge">
            <i class="fas fa-user-shield"></i>
            {{ $roleLabel }}
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-bolt"></i> Quick Actions</span>
        </div>
        <div class="dash-panel-body">
            <div class="quick-actions">
                @if($canSales)<a class="quick-btn" href="{{ \Illuminate\Support\Facades\Route::has('sales.index') ? route('sales.index') : '#' }}"><i class="fas fa-cash-register"></i> New Sale</a>@endif
                @if($canContracts)<a class="quick-btn" href="{{ \Illuminate\Support\Facades\Route::has('contracts.index') ? route('contracts.index') : '#' }}"><i class="fas fa-file-contract"></i> Contracts</a>@endif
                @if($canPayments)<a class="quick-btn" href="{{ \Illuminate\Support\Facades\Route::has('payments.index') ? route('payments.index') : '#' }}"><i class="fas fa-money-bill-transfer"></i> Payments</a>@endif
                @if($canHarvests)<a class="quick-btn" href="{{ \Illuminate\Support\Facades\Route::has('harvests.index') ? route('harvests.index') : '#' }}"><i class="fas fa-warehouse"></i> Harvests</a>@endif
                @if($canActivities)<a class="quick-btn secondary" href="{{ \Illuminate\Support\Facades\Route::has('activities.index') ? route('activities.index') : '#' }}"><i class="fas fa-list-check"></i> Activities</a>@endif
                @if($canMembers)<a class="quick-btn secondary" href="{{ \Illuminate\Support\Facades\Route::has('members.index') ? route('members.index') : '#' }}"><i class="fas fa-users"></i> Members</a>@endif
            </div>
        </div>
    </div>

    @if($canSales || $canProducts || $canStock || $canCustomers || $canOrders || $canExpenses || $canSuppliers || $canBranches)
        <div class="dash-section">
            <h2 class="dash-section-title"><i class="fas fa-store"></i> Non-Membership</h2>
            <div class="dash-grid">
                @if($canSales)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('sales.index') ? route('sales.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Today Sales</span>
                            <span class="dash-card-icon"><i class="fas fa-cash-register"></i></span>
                        </div>
                        <div class="dash-card-value">{{ $money($salesTodayTotal) }}</div>
                        <div class="dash-card-desc">{{ number_format($salesTodayCount) }} transaction(s)</div>
                    </a>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('sales-reports.index') ? route('sales-reports.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Monthly Sales</span>
                            <span class="dash-card-icon"><i class="fas fa-chart-line"></i></span>
                        </div>
                        <div class="dash-card-value">{{ $money($salesMonthTotal) }}</div>
                        <div class="dash-card-desc">{{ number_format($salesMonthCount) }} transaction(s)</div>
                    </a>
                @endif

                @if($canProducts)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('products.index') ? route('products.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Products</span>
                            <span class="dash-card-icon"><i class="fas fa-box"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($productsCount) }}</div>
                        <div class="dash-card-desc">Inventory products</div>
                    </a>
                @endif

                @if($canStock)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('stock.index') ? route('stock.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Stock</span>
                            <span class="dash-card-icon"><i class="fas fa-warehouse"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($stockCount ?? 0) }}</div>
                        <div class="dash-card-desc">Stock records</div>
                    </a>
                @endif

                @if($canCustomers)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('customers.index') ? route('customers.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Customers</span>
                            <span class="dash-card-icon"><i class="fas fa-users"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($customersCount) }}</div>
                        <div class="dash-card-desc">Registered customers</div>
                    </a>
                @endif

                @if($canOrders)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('orders.index') ? route('orders.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Orders</span>
                            <span class="dash-card-icon"><i class="fas fa-shopping-cart"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($ordersCount) }}</div>
                        <div class="dash-card-desc">Customer orders</div>
                    </a>
                @endif

                @if($canExpenses)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('expenses.index') ? route('expenses.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Expenses</span>
                            <span class="dash-card-icon"><i class="fas fa-receipt"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($expensesCount) }}</div>
                        <div class="dash-card-desc">Expense records</div>
                    </a>
                @endif

                @if($canSuppliers)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('suppliers.index') ? route('suppliers.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Suppliers</span>
                            <span class="dash-card-icon"><i class="fas fa-truck"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($suppliersCount) }}</div>
                        <div class="dash-card-desc">Supplier records</div>
                    </a>
                @endif

                @if($canBranches)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('branches.index') ? route('branches.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Branches</span>
                            <span class="dash-card-icon"><i class="fas fa-building"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($branchesCount) }}</div>
                        <div class="dash-card-desc">Business branches</div>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if($canMembers || $canContracts || $canProjects || $canGroups || $canActivities || $canHarvests || $canPayments || $canReceivables || $canTermination || $canMeetings || $canMobilizers)
        <div class="dash-section">
            <h2 class="dash-section-title"><i class="fas fa-people-group"></i> Membership</h2>
            <div class="dash-grid">
                @if($canMembers)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('members.index') ? route('members.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Members</span>
                            <span class="dash-card-icon"><i class="fas fa-users"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($membersCount) }}</div>
                        <div class="dash-card-desc">Registered members</div>
                    </a>
                @endif

                @if($canContracts)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('contracts.index') ? route('contracts.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Contracts</span>
                            <span class="dash-card-icon"><i class="fas fa-file-contract"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($contractsCount) }}</div>
                        <div class="dash-card-desc">All contracts</div>
                    </a>
                @endif

                @if($canProjects)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('projects.index') ? route('projects.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Projects</span>
                            <span class="dash-card-icon"><i class="fas fa-diagram-project"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($projectsCount) }}</div>
                        <div class="dash-card-desc">Membership projects</div>
                    </a>
                @endif

                @if($canGroups)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('groups.index') ? route('groups.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Groups</span>
                            <span class="dash-card-icon"><i class="fas fa-layer-group"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($groupsCount) }}</div>
                        <div class="dash-card-desc">Member groups</div>
                    </a>
                @endif

                @if($canActivities)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('activities.index') ? route('activities.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Activities</span>
                            <span class="dash-card-icon"><i class="fas fa-list-check"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($activitiesCount) }}</div>
                        <div class="dash-card-desc">Activities and promotions</div>
                    </a>
                @endif

                @if($canHarvests)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('harvests.index') ? route('harvests.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Harvests</span>
                            <span class="dash-card-icon"><i class="fas fa-warehouse"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($harvestsCount) }}</div>
                        <div class="dash-card-desc">Recorded harvests</div>
                    </a>
                    <a href="{{ \Illuminate\Support\Facades\Route::has('harvest-due.index') ? route('harvest-due.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Harvests Due</span>
                            <span class="dash-card-icon"><i class="fas fa-calendar-check"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($harvestsDueCount) }}</div>
                        <div class="dash-card-desc">Due for harvest</div>
                    </a>
                @endif

                @if($canPayments)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('payments.index') ? route('payments.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Payments</span>
                            <span class="dash-card-icon"><i class="fas fa-money-bill-transfer"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($paymentsCount) }}</div>
                        <div class="dash-card-desc">{{ $money($totalPayments) }}</div>
                    </a>
                @endif

                @if($canReceivables)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('receivables.index') ? route('receivables.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Receivables</span>
                            <span class="dash-card-icon"><i class="fas fa-money-bill-trend-up"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($receivablesCount) }}</div>
                        <div class="dash-card-desc">{{ $money($totalReceivables) }}</div>
                    </a>
                @endif

                @if($canTermination)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('termination.index') ? route('termination.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Terminated</span>
                            <span class="dash-card-icon"><i class="fas fa-calendar-xmark"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($terminatedCount) }}</div>
                        <div class="dash-card-desc">Closed or terminated contracts</div>
                    </a>
                @endif

                @if($canMeetings)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('meetings.index') ? route('meetings.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Meetings</span>
                            <span class="dash-card-icon"><i class="fas fa-calendar-days"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($meetingsCount) }}</div>
                        <div class="dash-card-desc">Scheduled meetings</div>
                    </a>
                @endif

                @if($canMobilizers)
                    <a href="{{ \Illuminate\Support\Facades\Route::has('mobilizers.index') ? route('mobilizers.index') : '#' }}" class="dash-card">
                        <div class="dash-card-top">
                            <span class="dash-card-title">Mobilizers</span>
                            <span class="dash-card-icon"><i class="fas fa-users-cog"></i></span>
                        </div>
                        <div class="dash-card-value">{{ number_format($mobilizersCount) }}</div>
                        <div class="dash-card-desc">Mobilizer records</div>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if($canStock && !empty($stockData))
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-layer-group"></i> Stock Levels</span>
                <a href="{{ \Illuminate\Support\Facades\Route::has('stock.index') ? route('stock.index') : '#' }}" class="quick-btn secondary">View All</a>
            </div>

            <div class="dash-panel-body">
                @php
                    $maxQty = max(array_map(fn ($row) => (float) ($row['quantity'] ?? 0), $stockData)) ?: 1;
                @endphp

                <div class="stock-list">
                    @foreach($stockData as $item)
                        @php
                            $qty = (float) ($item['quantity'] ?? 0);
                            $pct = min(100, round(($qty / $maxQty) * 100));
                        @endphp
                        <div>
                            <div class="stock-row-top">
                                <span class="stock-name">{{ $item['product_name'] ?? '-' }}</span>
                                <span class="stock-qty">{{ number_format($qty) }} units</span>
                            </div>
                            <div class="stock-track">
                                <div class="stock-fill" style="width:{{ (int) $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

</div>
@endsection