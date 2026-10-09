<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Harvest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessReportController extends ReportController
{
    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('auth'),
            new \Illuminate\Routing\Controllers\Middleware('user.status'),
            new \Illuminate\Routing\Controllers\Middleware('permission:view_business_report'),
            (new \Illuminate\Routing\Controllers\Middleware('permission:export_business_report'))->only(['export']),
        ];
    }

    public const SECTIONS = ['overview', 'customers', 'sales', 'stock', 'profit', 'products', 'suppliers', 'purchases', 'expenses'];

    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId = $this->selectedBranch($request);
        $branches = $this->branches();
        $section = in_array($request->query('section', 'overview'), self::SECTIONS, true)
            ? $request->query('section', 'overview')
            : 'overview';

        $data = array_merge(
            $this->salesStats($from, $to, $branchId),
            $this->registryStats($from, $to, $branchId),
            $this->inventoryStats($from, $to, $branchId),
            $this->expenseStats($from, $to, $branchId)
        );

        $sections = [
            'customers' => $this->customerSection($from, $to, $branchId),
            'sales' => $this->salesSection($from, $to, $branchId),
            'stock' => $this->inventorySection($branchId),
            'profit' => $this->profitSection($from, $to, $branchId),
            'products' => $this->productSection($branchId),
            'suppliers' => $this->supplierSection($branchId),
            'purchases' => $this->purchaseSection($from, $to, $branchId),
            'expenses' => $this->expenseSection($from, $to, $branchId),
        ];

        return view('reports.business.index', compact(
            'from',
            'to',
            'branchId',
            'branches',
            'section',
            'data',
            'sections'
        ));
    }

    public function export(Request $request)
    {
        if (!app(\App\Services\PermissionService::class)->canAny(['export_business_report', 'reports_export'])) {
            abort(403, 'You do not have permission to export the business report.');
        }

        [$from, $to] = $this->dateRange($request);
        $branchId = $this->selectedBranch($request);
        $section = in_array($request->query('section', 'overview'), self::SECTIONS, true)
            ? $request->query('section', 'overview')
            : 'overview';

        $filename = 'business_report_' . $section . '_' . $from . '_' . $to . '.csv';

        $rows = collect();
        $headers = ['Metric', 'Value'];

        if ($section === 'overview') {
            $map = [
                'Total Sales' => 'sales_count',
                'Revenue Collected' => 'revenue_collected',
                'Pending Amount' => 'pending_amount',
                'Active Customers' => 'active_customers',
                'Registered Customers' => 'total_customers',
                'Total Products' => 'total_products',
                'Stock Quantity' => 'total_stock_qty',
                'Stock Value' => 'total_stock_value',
                'Total Expenses' => 'total_expenses',
                'Harvest Payouts' => 'harvest_payouts',
                'Net Position' => 'net_position',
            ];
            $stats = array_merge(
                $this->salesStats($from, $to, $branchId),
                $this->registryStats($from, $to, $branchId),
                $this->inventoryStats($from, $to, $branchId),
                $this->expenseStats($from, $to, $branchId)
            );
            $rows = collect($map)->map(fn ($key, $label) => [$label, $stats[$key] ?? 0]);
        } else {
            [$headers, $rows] = $this->sectionExport($section, $from, $to, $branchId);
        }

        return $this->streamCsv($filename, $headers, $rows, fn (array $row) => $row);
    }

    /* ---------------------------------------------------------------
       Overview stats
    --------------------------------------------------------------- */

    protected function salesStats(string $from, string $to, int $branchId): array
    {
        $query = Order::query()
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        $rows = collect($query->get(['status', 'total_amount', 'customer_id']));

        return [
            'sales_count' => $rows->count(),
            'revenue_collected' => (float) $rows->whereIn('status', ['completed', 'delivered', 'paid', 'confirmed'])->sum('total_amount'),
            'pending_amount' => (float) $rows->whereIn('status', ['pending', 'processing'])->sum('total_amount'),
            'active_customers' => $rows->whereNotNull('customer_id')->pluck('customer_id')->unique()->count(),
        ];
    }

    protected function registryStats(string $from, string $to, int $branchId): array
    {
        $customers = Customer::query();
        if ($branchId > 0) {
            $customers->where('branch_id', $branchId);
        }
        $customerCount = $customers->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->count();

        return [
            'total_customers' => $customerCount,
            'total_products' => Product::count(),
            'total_suppliers' => Supplier::count(),
        ];
    }

    protected function inventoryStats(string $from, string $to, int $branchId): array
    {
        $stock = Stock::query()->with('product');
        if ($branchId > 0) {
            $stock->where('branch_id', $branchId);
        }

        $stockRows = $stock->get(['id', 'product_id', 'quantity', 'cost_price']);

        return [
            'total_products' => $stockRows->pluck('product_id')->unique()->count(),
            'total_stock_qty' => (float) $stockRows->sum('quantity'),
            'total_stock_value' => (float) $stockRows->sum(fn ($row) => (float) $row->quantity * (float) $row->cost_price),
        ];
    }

    protected function expenseStats(string $from, string $to, int $branchId): array
    {
        $expenses = Expense::query();
        if ($branchId > 0) {
            $expenses->where('branch_id', $branchId);
        }
        $manualTotal = (float) $expenses->whereBetween('expense_date', [$from, $to])->sum('amount');

        $harvest = Harvest::query()
            ->whereRaw("(LOWER(COALESCE(`status`, '')) = 'paid' OR LOWER(COALESCE(`approval_stage`, '')) = 'paid')");
        if ($branchId > 0) {
            $harvest->where('branch_id', $branchId);
        }
        $paidHarvest = $harvest->get()
            ->filter(fn (Harvest $h) => $this->paidDateInRange($h, $from, $to))
            ->sum(fn (Harvest $h) => (float) ($h->net_amount ?? $h->amount_harvested ?? $h->equivalent_ugx ?? 0));

        $harvestPayouts = (float) $paidHarvest;

        return [
            'total_expenses' => $manualTotal,
            'harvest_payouts' => $harvestPayouts,
            'net_position' => $this->salesStats($from, $to, $branchId)['revenue_collected'] - $manualTotal - $harvestPayouts,
        ];
    }

    protected function paidDateInRange(Harvest $h, string $from, string $to): bool
    {
        $date = $h->paid_at ?? $h->approved_at ?? $h->harvest_date ?? $h->created_at;
        $date = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : substr((string) $date, 0, 10);

        return $date !== '' && $date >= $from && $date <= $to;
    }

    /* ---------------------------------------------------------------
       Section data
    --------------------------------------------------------------- */

    protected function customerSection(string $from, string $to, int $branchId): array
    {
        $customers = Customer::query()->withCount([
            'orders as order_count' => fn ($q) => $q->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']),
        ]);
        if ($branchId > 0) {
            $customers->where('branch_id', $branchId);
        }

        $rows = $customers->get(['id', 'name', 'phone', 'email', 'customer_type', 'created_at'])
            ->filter(fn ($c) => $c->order_count > 0)
            ->sortByDesc('order_count')
            ->take(15)
            ->values();

        return [
            'total' => Customer::query()->tap(fn ($q) => $branchId > 0 ? $q->where('branch_id', $branchId) : $q)->count(),
            'rows' => $rows,
        ];
    }

    protected function salesSection(string $from, string $to, int $branchId): array
    {
        $query = Order::query()
            ->with('branch')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        return ['rows' => $query->orderByDesc('created_at')->get()];
    }

    protected function inventorySection(int $branchId): array
    {
        $stock = Stock::query()->with(['product.category', 'branch']);
        if ($branchId > 0) {
            $stock->where('branch_id', $branchId);
        }



        return [
            'rows' => $stock->orderBy('product_id')->get()
                ->map(function (Stock $row)  {
                    $soldQty = max(0, (int) $row->total_stock - (int) $row->quantity);
                    $remaining = max(0, (int) $row->quantity);

                    return [
                        'product' => $row->product,
                        'branch_name' => $row->branch->name ?? '-',
                        'total_stock' => (int) $row->total_stock,
                        'sold_qty' => $soldQty,
                        'remaining' => $remaining,
                        'value' => $remaining * (float) $row->cost_price,
                    ];
                }),
        ];
    }

    protected function soldQtyMap(): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['cancelled'])
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as sold_quantity')->pluck('sold_quantity', 'order_items.product_id')
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (int) $qty])
            ->all();
    }

    protected function profitSection(string $from, string $to, int $branchId): array
    {
        $items = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['cancelled'])
            ->whereBetween('orders.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($branchId > 0, fn ($query) => $query->where('orders.branch_id', $branchId))
            ->get(['order_items.product_id', 'order_items.quantity', 'order_items.subtotal']);

        $costMap = $this->avgCostMap($branchId);
        $products = Product::query()->pluck('name', 'id');

        $groups = $items->groupBy('product_id')->map(function ($rows, $productId) use ($costMap, $products) {
            $qty = (int) $rows->sum('quantity');
            $revenue = (float) $rows->sum('subtotal');
            $cost = $qty * (float) ($costMap[(int) $productId] ?? 0);
            $profit = $revenue - $cost;
            $margin = $cost > 0 ? ($profit / $cost) * 100 : 0;

            return [
                'product_name' => $products[(int) $productId] ?? 'Product #' . $productId,
                'qty_sold' => $qty,
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit,
                'margin' => $margin,
            ];
        });

        $totals = [
            'total_qty' => (int) $groups->sum('qty_sold'),
            'total_revenue' => (float) $groups->sum('revenue'),
            'total_cost' => (float) $groups->sum('cost'),
            'total_profit' => (float) $groups->sum('profit'),
        ];
        $totals['total_margin'] = $totals['total_cost'] > 0 ? ($totals['total_profit'] / $totals['total_cost']) * 100 : 0;

        return [
            'rows' => $groups->sortByDesc('profit')->values(),
            'totals' => $totals,
        ];
    }

    protected function avgCostMap(int $branchId): array
    {
        return Stock::query()
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->whereNotNull('cost_price')
            ->orderBy('product_id')
            ->orderBy('cost_price')
            ->get(['product_id', 'cost_price'])
            ->groupBy('product_id')
            ->map(fn ($rows) => (float) $rows->avg('cost_price'))
            ->all();
    }

    protected function productSection(int $branchId): array
    {
        $products = Product::query()
            ->with('category')
            ->withSum(['stock as total_quantity' => fn ($query) => $query->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId))], 'quantity');
        if ($branchId > 0) {
            $products->whereHas('stock', fn ($q) => $q->where('branch_id', $branchId));
        }

        return [
            'total' => Product::count(),
            'rows' => $products->orderBy('name')->get(),
        ];
    }

    protected function supplierSection(int $branchId): array
    {
        $supplierSpend = Stock::query()
            ->whereNotNull('supplier_id')
            ->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId))
            ->get(['supplier_id', 'total_stock', 'cost_price'])
            ->groupBy('supplier_id')
            ->map(fn ($rows) => (float) $rows->sum(fn ($r) => (float) $r->total_stock * (float) $r->cost_price));

        $suppliers = Supplier::query();
        if ($branchId > 0) {
            $suppliers->whereHas('stocks', fn ($q) => $q->where('branch_id', $branchId));
        }

        return [
            'total' => Supplier::count(),
            'rows' => $suppliers->orderBy('name')->get(['id', 'name', 'contact_name', 'phone', 'email'])
                ->map(fn ($s) => ['supplier' => $s, 'spend' => $supplierSpend[(int) $s->id] ?? 0.0]),
        ];
    }

    protected function purchaseSection(string $from, string $to, int $branchId): array
    {
        $stock = Stock::query()->with(['supplier', 'branch']);
        if ($branchId > 0) {
            $stock->where('branch_id', $branchId);
        }

        return [
            'rows' => $stock->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Stock $row) => [
                    'supplier_name' => $row->supplier->name ?? '-',
                    'branch_name' => $row->branch->name ?? '-',
                    'quantity' => (int) $row->total_stock,
                    'cost_price' => (float) $row->cost_price,
                    'total' => (int) $row->total_stock * (float) $row->cost_price,
                    'created_at' => $row->created_at?->format('Y-m-d'),
                ]),
        ];
    }

    protected function expenseSection(string $from, string $to, int $branchId): array
    {
        $expenses = Expense::query()->with('branch')->whereBetween('expense_date', [$from, $to]);
        if ($branchId > 0) {
            $expenses->where('branch_id', $branchId);
        }

        return [
            'total' => (float) (clone $expenses)->sum('amount'),
            'rows' => $expenses->orderByDesc('expense_date')->get(),
        ];
    }

    protected function sectionExport(string $section, string $from, string $to, int $branchId): array
    {
        return match ($section) {
            'customers' => [
                ['Customer', 'Phone', 'Email', 'Type', 'Orders In Period'],
                $this->customerSection($from, $to, $branchId)['rows']->map(fn ($c) => [
                    $c->name,
                    $c->phone,
                    $c->email,
                    $c->customer_type,
                    $c->order_count,
                ]),
            ],
            'sales' => [
                ['Order #', 'Date', 'Customer', 'Branch', 'Method', 'Status', 'Total'],
                $this->salesSection($from, $to, $branchId)['rows']->map(fn (Order $o) => [
                    $o->order_number,
                    $o->created_at?->format('Y-m-d H:i'),
                    $o->customer_name,
                    $o->branch->name ?? '',
                    $o->payment_method,
                    $o->status,
                    $o->total_amount,
                ]),
            ],
            'stock' => [
                ['Product', 'Branch', 'Total Stock', 'Sold', 'Remaining', 'Stock Value'],
                $this->inventorySection($branchId)['rows']->map(fn ($r) => [
                    $r['product']->name ?? '',
                    $r['branch_name'],
                    $r['total_stock'],
                    $r['sold_qty'],
                    $r['remaining'],
                    $r['value'],
                ]),
            ],
            'profit' => [
                ['Product', 'Qty Sold', 'Revenue', 'Cost', 'Profit', 'Margin %'],
                $this->profitSection($from, $to, $branchId)['rows']->map(fn ($r) => [
                    $r['product_name'],
                    $r['qty_sold'],
                    $this->moneyExport((float) $r['revenue']),
                    $this->moneyExport((float) $r['cost']),
                    $this->moneyExport((float) $r['profit']),
                    number_format((float) $r['margin'], 1),
                ]),
            ],
            'products' => [
                ['Product', 'SKU', 'Category', 'Stock Qty'],
                $this->productSection($branchId)['rows']->map(fn (Product $p) => [
                    $p->name,
                    $p->sku,
                    $p->category->name ?? '',
                    $p->total_quantity,
                ]),
            ],
            'suppliers' => [
                ['Supplier', 'Contact', 'Phone', 'Email', 'Purchases (UGX)'],
                $this->supplierSection($branchId)['rows']->map(fn ($r) => [
                    $r['supplier']->name,
                    $r['supplier']->contact_name,
                    $r['supplier']->phone,
                    $r['supplier']->email,
                    $this->moneyExport((float) $r['spend']),
                ]),
            ],
            'purchases' => [
                ['Supplier', 'Branch', 'Date', 'Quantity', 'Unit Cost', 'Total'],
                $this->purchaseSection($from, $to, $branchId)['rows']->map(fn ($r) => [
                    $r['supplier_name'],
                    $r['branch_name'],
                    $r['created_at'],
                    $r['quantity'],
                    $this->moneyExport($r['cost_price']),
                    $this->moneyExport($r['total']),
                ]),
            ],
            'expenses' => [
                ['Date', 'Title', 'Category', 'Method', 'Branch', 'Amount'],
                $this->expenseSection($from, $to, $branchId)['rows']->map(fn (Expense $e) => [
                    $e->expense_date?->format('Y-m-d'),
                    $e->title,
                    $e->category,
                    $e->payment_method,
                    $e->branch->name ?? '',
                    $e->amount,
                ]),
            ],
            default => [['Metric', 'Value'], collect()],
        };
    }
}
