<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesReportController extends ReportController
{
    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('auth'),
            new \Illuminate\Routing\Controllers\Middleware('user.status'),
            new \Illuminate\Routing\Controllers\Middleware('permission:view_sales_report'),
            (new \Illuminate\Routing\Controllers\Middleware('permission:export_sales_report'))->only(['export']),
        ];
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId = $this->selectedBranch($request);
        $branches = $this->branches();
        $source = $this->source($request);
        $posAvailable = $this->posAvailable();
        $data = $this->compute($from, $to, $branchId, $source);

        return view('reports.sales.index', compact('from', 'to', 'branchId', 'branches', 'data', 'source', 'posAvailable'));
    }

    public function export(Request $request)
    {
        if (!app(\App\Services\PermissionService::class)->canAny(['export_sales_report', 'reports_export'])) {
            abort(403, 'You do not have permission to export the sales report.');
        }

        [$from, $to] = $this->dateRange($request);
        $branchId = $this->selectedBranch($request);
        $source = $this->source($request);
        $data = $this->compute($from, $to, $branchId, $source);

        $filename = 'sales_report_' . $source . '_' . $from . '_' . $to . '.csv';

        $rows = collect($data['daily'])->map(fn ($d) => [
            $d['date'],
            $d['orders'],
            $this->moneyExport((float) $d['revenue']),
            $this->moneyExport((float) $d['collected']),
            $this->moneyExport((float) $d['pending']),
        ]);

        $headers = ['Date', 'Transactions', 'Revenue', 'Collected', 'Pending'];

        return $this->streamCsv($filename, $headers, $rows, fn ($r) => $r);
    }

    protected function posAvailable(): bool
    {
        return Schema::hasTable('sales') && Schema::hasColumns('sales', ['id', 'sale_date', 'total_amount', 'balance_amount', 'branch_id', 'payment_method'])
            && Schema::hasTable('sale_items') && Schema::hasColumns('sale_items', ['sale_id', 'product_id', 'quantity', 'total']);
    }

    protected function source(Request $request): string
    {
        $source = $request->query('source', $this->posAvailable() ? 'pos' : 'orders');
        abort_unless(in_array($source, ['pos', 'orders'], true), 422, 'Select a valid sales report source.');
        abort_if($source === 'pos' && !$this->posAvailable(), 422, 'POS reporting requires the sales-module migration.');
        return $source;
    }

    protected function compute(string $from, string $to, int $branchId, string $source): array
    {
        if ($source === 'pos') {
            $query = DB::table('sales')->whereBetween('sale_date', [$from, $to]);
            if ($branchId > 0) $query->where('branch_id', $branchId);
            $orderRows = $query->get(['id', 'sale_date', 'total_amount', 'balance_amount', 'branch_id', 'payment_method'])->map(function ($sale) {
                $sale->created_at = \Carbon\Carbon::parse($sale->sale_date);
                $sale->collected_amount = max(0, (float) $sale->total_amount - (float) $sale->balance_amount);
                $sale->pending_amount = (float) $sale->balance_amount;
                return $sale;
            });
            $methodNames = Schema::hasTable('payment_methods') && Schema::hasColumn('payment_methods', 'method_name')
                ? DB::table('payment_methods')->pluck('method_name', 'id') : collect();
            $orderRows->each(function ($sale) use ($methodNames) {
                $sale->payment_method = $methodNames->get($sale->payment_method, 'Method '.$sale->payment_method);
            });
            $items = DB::table('sale_items')->whereIn('sale_id', $orderRows->pluck('id'))
                ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                ->get(['sale_items.product_id', 'products.name as product_name', 'sale_items.quantity', 'sale_items.total as subtotal']);
        } else {
        $orders = Order::query()
            ->with('branch')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        if ($branchId > 0) {
            $orders->where('branch_id', $branchId);
        }

        $orderRows = (clone $orders)->get(['id', 'created_at', 'total_amount', 'status', 'branch_id', 'payment_method', 'customer_name']);
        $orderRows->each(function ($order) {
            $order->collected_amount = in_array($order->status, ['completed', 'delivered', 'paid', 'confirmed'], true) ? (float) $order->total_amount : 0;
            $order->pending_amount = in_array($order->status, ['pending', 'processing'], true) ? (float) $order->total_amount : 0;
        });
        $orderIds = $orderRows->pluck('id')->all();

        $items = OrderItem::query()->whereIn('order_id', $orderIds)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->get(['order_items.product_id', 'order_items.product_name', 'order_items.quantity', 'order_items.subtotal', 'orders.branch_id']);
        }

        // Daily trend
        $daily = $orderRows->groupBy(fn ($o) => $o->created_at?->format('Y-m-d') ?? '')
            ->map(function ($rows, $date) {
                $collected = $rows->sum('collected_amount');
                $pending = $rows->sum('pending_amount');
                return [
                    'date' => $date,
                    'orders' => $rows->count(),
                    'revenue' => $rows->sum('total_amount'),
                    'collected' => $collected,
                    'pending' => $pending,
                ];
            })
            ->sortKeys()
            ->values();

        // Top products
        $topProducts = $items->groupBy('product_id')
            ->map(function ($rows, $pid) {
                $name = $rows->first()->product_name;
                return [
                    'product_id' => (int) $pid,
                    'product_name' => $name,
                    'qty' => (int) $rows->sum('quantity'),
                    'revenue' => (float) $rows->sum('subtotal'),
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        // By branch
        $branches = $this->branches();
        $byBranch = $orderRows->groupBy('branch_id')
            ->map(function ($rows, $bid) use ($branches) {
                $name = $branches->firstWhere('id', (int) $bid)->name ?? 'Unknown';
                return [
                    'branch' => $name,
                    'orders' => $rows->count(),
                    'revenue' => (float) $rows->sum('total_amount'),
                    'collected' => (float) $rows->sum('collected_amount'),
                ];
            })
            ->values();

        // By payment method
        $byPayment = $orderRows->groupBy('payment_method')
            ->map(function ($rows, $method) {
                return [
                    'method' => $method,
                    'orders' => $rows->count(),
                    'revenue' => (float) $rows->sum('total_amount'),
                ];
            })
            ->values();

        $totals = [
            'orders' => $orderRows->count(),
            'revenue' => (float) $orderRows->sum('total_amount'),
            'collected' => (float) $orderRows->sum('collected_amount'),
            'pending' => (float) $orderRows->sum('pending_amount'),
            'avg' => $orderRows->count() > 0 ? (float) $orderRows->avg('total_amount') : 0,
        ];

        return compact('totals', 'daily', 'topProducts', 'byBranch', 'byPayment');
    }
}
