<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected PermissionService $permission;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
        ];
    }

    public function index(): View|RedirectResponse
    {
        $user = auth()->user();
        $role = $this->permission->normalizeRole($user->role);

        // Legacy guard: only dashboard roles may open the dashboard.
        if (!in_array($role, $this->permission->dashboardRoles(), true)) {
            return redirect()->route($this->permission->homeUrl($role));
        }

        $displayName = $user->name ?? $user->email ?? 'User';

        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $canSales = $this->dashCan(['manage_sales', 'sales', 'sales_report', 'view_sales_report']);
        $canProducts = $this->dashCan(['manage_products', 'products']);
        $canStock = $this->dashCan(['manage_stock', 'stock', 'stock_report', 'view_stock_report']);
        $canCustomers = $this->dashCan(['manage_customers', 'customers', 'manage_users']);
        $canOrders = $this->dashCan(['manage_orders', 'orders']);
        $canExpenses = $this->dashCan(['manage_expenses', 'expenses']);
        $canSuppliers = $this->dashCan(['manage_suppliers', 'suppliers']);
        $canBranches = $this->dashCan(['manage_branches', 'branches']);

        $canMembers = $this->dashCan(['members', 'members_view', 'manage_membership']);
        $canContracts = $this->dashCan(['contracts', 'contracts_view', 'manage_membership']);
        $canProjects = $this->dashCan(['projects', 'projects_view', 'manage_membership']);
        $canGroups = $this->dashCan(['groups', 'groups_view', 'manage_membership']);
        $canActivities = $this->dashCan(['activities', 'activities_view', 'manage_membership']);
        $canHarvests = $this->dashCan(['harvests', 'harvest_due', 'harvests_view', 'manage_membership']);
        $canPayments = $this->dashCan(['payments', 'payments_view', 'manage_membership']);
        $canReceivables = $this->dashCan(['receivables', 'receivables_view', 'manage_membership']);
        $canTermination = $this->dashCan(['termination', 'termination_view', 'manage_membership']);
        $canMeetings = $this->dashCan(['meetings', 'meetings_view', 'manage_membership']);
        $canMobilizers = $this->dashCan(['mobilizers', 'view_mobilizers', 'manage_membership']);

        $salesTodayCount = $this->countRows('sales', 'DATE(sale_date) = ?', [$today]);
        $salesTodayTotal = $this->sumRows('sales', 'total_amount', 'DATE(sale_date) = ?', [$today]);

        $salesMonthCount = $this->countRows('sales', 'DATE(sale_date) BETWEEN ? AND ?', [$monthStart, $monthEnd]);
        $salesMonthTotal = $this->sumRows('sales', 'total_amount', 'DATE(sale_date) BETWEEN ? AND ?', [$monthStart, $monthEnd]);

        $productsCount = $this->countRows('products');
        $stockCount = $this->countRows('stock');
        $customersCount = $this->countRows('customers');
        $ordersCount = $this->countRows('orders');
        $expensesCount = $this->countRows('expenses');
        $suppliersCount = $this->countRows('suppliers');
        $branchesCount = $this->countRows('branches');

        $membersCount = $this->countRows('members');
        $contractsCount = $this->countRows('contracts');
        $projectsCount = $this->countRows('projects');
        $groupsCount = $this->countRows('groups');
        $activitiesCount = $this->countRows('activities');
        $harvestsCount = $this->countRows('harvests');
        $paymentsCount = $this->countRows('payment_transactions');
        $receivablesCount = $this->countRows('receivables');
        $meetingsCount = $this->countRows('meetings');
        $mobilizersCount = $this->countRows('mobilizers');

        $terminatedCount = 0;
        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'status')) {
            $terminatedCount = $this->countRows(
                'contracts',
                "LOWER(status) IN ('terminated','closed','cancelled')"
            );
        }

        $harvestsDueCount = 0;
        if (Schema::hasTable('contracts')) {
            if (Schema::hasColumn('contracts', 'next_harvest_date')) {
                $harvestsDueCount = $this->countRows('contracts', 'next_harvest_date <= CURDATE()');
            } elseif (Schema::hasColumn('contracts', 'harvest_due_date')) {
                $harvestsDueCount = $this->countRows('contracts', 'harvest_due_date <= CURDATE()');
            } elseif (Schema::hasTable('harvest_due')) {
                $harvestsDueCount = $this->countRows('harvest_due');
            }
        }

        $totalPayments = $this->sumRows('payment_transactions', 'amount');
        $totalReceivables = $this->sumRows('receivables', 'amount');

        $stockData = [];
        if ($canStock && Schema::hasTable('stock') && Schema::hasTable('products')) {
            $nameColumn = Schema::hasColumn('products', 'name')
                ? 'name'
                : (Schema::hasColumn('products', 'product_name') ? 'product_name' : 'id');

            $stockData = DB::table('stock as s')
                ->join('products as p', 'p.id', '=', 's.product_id')
                ->selectRaw("p.`{$nameColumn}` as product_name, COALESCE(SUM(s.quantity), 0) as quantity")
                ->groupBy('s.product_id', "p.`{$nameColumn}`")
                ->orderByDesc('quantity')
                ->limit(8)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        return view('dashboard.index', compact(
            'user',
            'role',
            'displayName',
            'greeting',
            'canSales',
            'canProducts',
            'canStock',
            'canCustomers',
            'canOrders',
            'canExpenses',
            'canSuppliers',
            'canBranches',
            'canMembers',
            'canContracts',
            'canProjects',
            'canGroups',
            'canActivities',
            'canHarvests',
            'canPayments',
            'canReceivables',
            'canTermination',
            'canMeetings',
            'canMobilizers',
            'salesTodayCount',
            'salesTodayTotal',
            'salesMonthCount',
            'salesMonthTotal',
            'productsCount',
            'stockCount',
            'customersCount',
            'ordersCount',
            'expensesCount',
            'suppliersCount',
            'branchesCount',
            'membersCount',
            'contractsCount',
            'projectsCount',
            'groupsCount',
            'activitiesCount',
            'harvestsCount',
            'paymentsCount',
            'receivablesCount',
            'meetingsCount',
            'mobilizersCount',
            'terminatedCount',
            'harvestsDueCount',
            'totalPayments',
            'totalReceivables',
            'stockData',
        ));
    }

    /**
     * Legacy dash_can(): super roles pass, then a per-role fallback list,
     * then the real permission service.
     */
    protected function dashCan(array $permissions): bool
    {
        $user = auth()->user();
        $role = $this->permission->normalizeRole($user->role);

        if (in_array($role, $this->permission->superRoles(), true)) {
            return true;
        }

        $roleFallback = [
            'manager' => [
                'view_dashboard', 'dashboard', 'manage_membership', 'membership',
                'contracts', 'contracts_view', 'members', 'members_view', 'projects', 'projects_view',
                'groups', 'groups_view', 'activities', 'activities_view', 'termination',
                'receivables', 'receivables_view', 'harvests', 'harvest_due', 'mobilizers',
                'payments', 'payments_view', 'meetings', 'financial_reports', 'business_report',
                'sales_report', 'stock_report', 'low_stock', 'expiry_stock', 'notifications',
                'manage_ypa', 'manage_customers', 'manage_products', 'manage_orders',
                'manage_stock', 'manage_suppliers', 'manage_sales',
                'manage_expenses', 'view_business_report', 'view_sales_report',
                'view_stock_report', 'view_low_stock_report', 'view_expiry_stock_report',
            ],

            'head_records' => [
                'view_dashboard', 'dashboard', 'manage_membership', 'membership',
                'contracts', 'contracts_view', 'members', 'members_view', 'groups', 'groups_view',
                'activities', 'activities_view', 'activities_edit', 'termination',
                'harvests', 'harvest_due', 'mobilizers', 'meetings', 'notifications',
                'payments', 'payments_view', 'financial_reports', 'business_report',
                'sales_report', 'stock_report', 'low_stock', 'expiry_stock',
            ],

            'records_officer' => [
                'view_dashboard', 'dashboard', 'manage_membership', 'membership',
                'contracts', 'contracts_view', 'members', 'members_view', 'groups', 'groups_view',
                'activities', 'activities_view', 'activities_edit', 'termination',
                'harvests', 'harvest_due', 'mobilizers', 'meetings', 'notifications',
                'payments', 'payments_view', 'financial_reports', 'business_report',
                'sales_report', 'stock_report', 'low_stock', 'expiry_stock',
            ],

            'finance_lead' => [
                'view_dashboard', 'dashboard', 'manage_membership', 'membership',
                'receivables', 'payments', 'payments_view', 'financial_reports', 'business_report',
                'harvests', 'contracts', 'contracts_view', 'manage_sales', 'view_sales_report',
                'sales_report', 'notifications',
            ],

            'accountant' => [
                'view_dashboard', 'dashboard', 'manage_membership', 'membership',
                'receivables', 'payments', 'payments_view', 'financial_reports', 'business_report',
                'contracts', 'contracts_view', 'manage_sales', 'view_sales_report',
                'sales_report', 'notifications',
            ],

            'payment_harvest' => [
                'view_dashboard', 'dashboard', 'manage_membership', 'membership',
                'payments', 'payments_view', 'harvests', 'harvest_due', 'receivables',
                'contracts', 'contracts_view', 'notifications',
            ],
        ];

        foreach ($permissions as $permission) {
            if (in_array($permission, $roleFallback[$role] ?? [], true)) {
                return true;
            }
        }

        return $this->permission->can($permissions);
    }

    protected function countRows(string $table, string $where = '', array $bindings = []): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if ($where !== '') {
            $query->whereRaw($where, $bindings);
        }

        return (int) $query->count();
    }

    protected function sumRows(string $table, string $column, string $where = '', array $bindings = []): float
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return 0.0;
        }

        $query = DB::table($table);

        if ($where !== '') {
            $query->whereRaw($where, $bindings);
        }

        return (float) $query->sum($column);
    }
}