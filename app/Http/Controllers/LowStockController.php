<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LowStockController extends ReportController
{
    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('auth'),
            new \Illuminate\Routing\Controllers\Middleware('user.status'),
            new \Illuminate\Routing\Controllers\Middleware('permission:view_low_stock_report'),
            (new \Illuminate\Routing\Controllers\Middleware('permission:export_low_stock_report'))->only(['export']),
        ];
    }

    public function index(Request $request)
    {
        $branchId = $this->selectedBranch($request);
        $branches = $this->branches();
        $categories = Category::orderBy('name')->get(['id', 'name']);

        $threshold = max(1, (int) $request->query('threshold', 10));
        $selectedCategory = $request->query('category');

        $data = $this->compute($branchId, $threshold, $request->query('category'));

        return view('reports.low-stock.index', compact(
            'branchId', 'branches', 'categories', 'threshold', 'data', 'selectedCategory'
        ));
    }

    public function export(Request $request)
    {
        if (!app(\App\Services\PermissionService::class)->canAny(['export_low_stock_report', 'reports_export'])) {
            abort(403, 'You do not have permission to export the low stock report.');
        }

        $branchId = $this->selectedBranch($request);
        $threshold = max(1, (int) $request->query('threshold', 10));
        $data = $this->compute($branchId, $threshold, $request->query('category'));

        $filename = 'low_stock_report_' . $threshold . '_' . now()->format('Ymd_His') . '.csv';

        $rows = $data['rows']->map(fn ($r) => [
            $r['product']->name ?? '',
            $r['product']->sku ?? '',
            $r['product']->category->name ?? '',
            $r['branch_name'],
            (int) $r['total_stock'],
            (int) $r['sold_qty'],
            (int) $r['remaining'],
            $r['status'],
            $this->moneyExport($r['value']),
        ]);

        $headers = ['Product', 'SKU', 'Category', 'Branch', 'In Stock', 'Sold', 'Remaining', 'Status', 'Value'];

        return $this->streamCsv($filename, $headers, $rows, fn ($r) => $r);
    }

    protected function compute(int $branchId, int $threshold, $category = null): array
    {
        $stock = Stock::query()->with(['product.category', 'branch']);

        if ($branchId > 0) {
            $stock->where('branch_id', $branchId);
        }

        if ($category) {
            $stock->whereHas('product', fn ($q) => $q->where('category_id', $category));
        }


        $stockRows = $stock->get();

        $rows = $stockRows->map(function (Stock $row) use ($threshold) {
            $soldQty = max(0, (int) $row->total_stock - (int) $row->quantity);
            $remaining = max(0, (int) $row->quantity);

            if ($remaining === 0) {
                $status = 'Out of Stock';
            } elseif ($remaining <= 5) {
                $status = 'Critical';
            } elseif ($remaining <= $threshold) {
                $status = 'Low';
            } else {
                return null;
            }

            return [
                'product' => $row->product,
                'branch_name' => $row->branch->name ?? '-',
                'total_stock' => (int) $row->total_stock,
                'sold_qty' => $soldQty,
                'remaining' => $remaining,
                'status' => $status,
                'value' => $remaining * (float) $row->cost_price,
            ];
        })->filter()->values();

        $counts = [
            'out' => $rows->where('status', 'Out of Stock')->count(),
            'critical' => $rows->where('status', 'Critical')->count(),
            'low' => $rows->where('status', 'Low')->count(),
        ];
        $counts['total'] = $counts['out'] + $counts['critical'] + $counts['low'];

        return compact('rows', 'counts', 'threshold');
    }

    protected function soldQtyMap(): array
    {
        return \App\Models\OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['cancelled'])
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as sold_quantity')->pluck('sold_quantity', 'order_items.product_id')
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (int) $qty])
            ->all();
    }
}
