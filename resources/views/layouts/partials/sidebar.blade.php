@php
    $permission = app(\App\Services\PermissionService::class);
    $user = auth()->user();
    $role = $permission->normalizeRole($user->role ?? '');
    $homeRoute = $permission->homeUrl($role);
    $homeUrl = \Illuminate\Support\Facades\Route::has($homeRoute) ? route($homeRoute) : '#';

    $nonMembershipItems = [
        ['route' => 'orders.index', 'label' => 'Orders', 'icon' => 'fas fa-shopping-cart', 'permission' => 'manage_orders'],
        ['route' => 'products.index', 'label' => 'Products', 'icon' => 'fas fa-box', 'permission' => 'manage_products'],
        ['route' => 'stock.index', 'label' => 'Stock', 'icon' => 'fas fa-warehouse', 'permission' => 'manage_stock'],
        ['route' => 'customers.index', 'label' => 'Customers', 'icon' => 'fas fa-users', 'permission' => 'manage_customers'],
        ['route' => 'suppliers.index', 'label' => 'Suppliers', 'icon' => 'fas fa-truck', 'permission' => 'manage_suppliers'],
        ['route' => 'branches.index', 'label' => 'Branches', 'icon' => 'fas fa-building', 'permission' => 'manage_branches'],
        ['route' => 'sales.index', 'label' => 'Point of Sales', 'icon' => 'fas fa-cash-register', 'permission' => 'manage_sales'],
        ['route' => 'expenses.index', 'label' => 'Expenses', 'icon' => 'fas fa-receipt', 'permission' => 'manage_expenses'],
        ['route' => 'business-report.index', 'label' => 'Business Report', 'icon' => 'fas fa-briefcase', 'permission' => 'view_business_report'],
        ['route' => 'sales-reports.index', 'label' => 'Sales Report', 'icon' => 'fas fa-dollar-sign', 'permission' => 'view_sales_report'],
        ['route' => 'stock-report.index', 'label' => 'Stock Report', 'icon' => 'fas fa-boxes-stacked', 'permission' => 'view_stock_report'],
        ['route' => 'low-stock.index', 'label' => 'Low Stock Report', 'icon' => 'fas fa-exclamation-triangle', 'permission' => 'view_low_stock_report'],
        ['route' => 'expiry-stock.index', 'label' => 'Expiry Stock Report', 'icon' => 'fas fa-calendar-times', 'permission' => 'view_expiry_stock_report'],
    ];

    $membershipItems = [
        ['route' => 'contracts.index', 'label' => 'Contracts', 'icon' => 'fas fa-file-contract', 'permission' => 'contracts'],
        ['route' => 'projects.index', 'label' => 'Projects', 'icon' => 'fas fa-diagram-project', 'permission' => 'projects'],
        ['route' => 'harvest-due.index', 'label' => 'Harvest Due', 'icon' => 'fas fa-calendar-check', 'permission' => 'harvest_due'],
        ['route' => 'harvests.index', 'label' => 'Harvests', 'icon' => 'fas fa-warehouse', 'permission' => 'harvests'],
        ['route' => 'termination.index', 'label' => 'Contract Termination', 'icon' => 'fas fa-calendar-xmark', 'permission' => 'termination'],
        ['route' => 'receivables.index', 'label' => 'Receivables', 'icon' => 'fas fa-money-bill-trend-up', 'permission' => 'receivables'],
        ['route' => 'payments.index', 'label' => 'Payments', 'icon' => 'fas fa-money-bill-transfer', 'permission' => 'payments'],
        ['route' => 'members.index', 'label' => 'Members & Groups', 'icon' => 'fas fa-users', 'permission' => 'members'],
        ['route' => 'groups.index', 'label' => 'Groups', 'icon' => 'fas fa-people-group', 'permission' => 'groups'],
        ['route' => 'mobilizers.index', 'label' => 'Mobilizers', 'icon' => 'fas fa-users-cog', 'permission' => 'mobilizers'],
        ['route' => 'meetings.index', 'label' => 'Meetings', 'icon' => 'fas fa-calendar-check', 'permission' => 'meetings'],
        ['route' => 'activities.index', 'label' => 'Activities', 'icon' => 'fas fa-list-check', 'permission' => 'activities'],
        ['route' => 'financial-reports.index', 'label' => 'Reports', 'icon' => 'fas fa-file-lines', 'permission' => 'financial_reports'],
    ];

    $showNonMembership = collect($nonMembershipItems)->contains(fn ($item) => $permission->can($item['permission']));
    $showMembership = collect($membershipItems)->contains(fn ($item) => $permission->can($item['permission']));
@endphp

<nav class="sidebar" id="sidebar">
    <div class="brand">
        <a href="{{ $homeUrl }}" class="brand-link">
            @if($siteLogo)
                <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }}" class="brand-logo">
            @endif
            <span class="brand-name">{{ $siteName }}</span>
        </a>
    </div>

    <ul class="nav-menu">

        @if($permission->can('view_dashboard'))
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-gauge-high"></i><span>Dashboard</span>
                </a>
            </li>
        @endif

        @if($showNonMembership)
            <li class="dropdown">
                <button class="dropdown-toggle" type="button" aria-expanded="false">
                    <span class="dropdown-label"><i class="fas fa-user-slash"></i><span>Non-membership</span></span>
                    <i class="fas fa-caret-down dropdown-icon"></i>
                </button>

                <ul class="dropdown-menu">
                    @foreach($nonMembershipItems as $item)
                        @if($permission->can($item['permission']))
                            <li>
                                <a href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}" class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">
                                    <i class="{{ $item['icon'] }}"></i> {{ $item['label'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>
        @endif

        @if($showMembership)
            <li class="dropdown">
                <button class="dropdown-toggle" type="button" aria-expanded="false">
                    <span class="dropdown-label"><i class="fas fa-users"></i><span>Membership</span></span>
                    <i class="fas fa-caret-down dropdown-icon"></i>
                </button>

                <ul class="dropdown-menu">
                    @foreach($membershipItems as $item)
                        @if($permission->can($item['permission']))
                            <li>
                                <a href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}" class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">
                                    <i class="{{ $item['icon'] }}"></i> {{ $item['label'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>
        @endif

        @if($permission->can('notifications'))
            <li><a href="{{ \Illuminate\Support\Facades\Route::has('notifications.index') ? route('notifications.index') : '#' }}" class="{{ request()->routeIs('notifications.index') ? 'active' : '' }}"><i class="fas fa-bell"></i> Notifications</a></li>
        @endif

        @if($permission->can('manage_users'))
            <li><a href="{{ \Illuminate\Support\Facades\Route::has('users.index') ? route('users.index') : '#' }}" class="{{ request()->routeIs('users.index') ? 'active' : '' }}"><i class="fas fa-user-cog"></i> Users</a></li>
        @endif

        @if($permission->can('manage_permissions'))
            <li><a href="{{ \Illuminate\Support\Facades\Route::has('permissions.index') ? route('permissions.index') : '#' }}" class="{{ request()->routeIs('permissions.index') ? 'active' : '' }}"><i class="fas fa-shield-halved"></i> Permissions</a></li>
        @endif

        @if($permission->can('manage_settings'))
            <li><a href="{{ \Illuminate\Support\Facades\Route::has('settings.index') ? route('settings.index') : '#' }}" class="{{ request()->routeIs('settings.index') ? 'active' : '' }}"><i class="fas fa-gear"></i> Settings</a></li>
        @endif

    </ul>
</nav>