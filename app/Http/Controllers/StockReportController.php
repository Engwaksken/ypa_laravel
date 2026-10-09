<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockReportController extends ReportController
{
    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('auth'),
            new \Illuminate\Routing\Controllers\Middleware('user.status'),
            new \Illuminate\Routing\Controllers\Middleware('permission:view_stock_report'),
            (new \Illuminate\Routing\Controllers\Middleware('permission:export_stock_report'))->only(['export']),
        ];
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId = $this->selectedBranch($request);
        $branches = $this->branches();
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $selectedCategory = $request->query('category');
        $selectedSupplier = $request->query('supplier');
        $selectedProduct = $request->query('product');

        $data = $this->compute($branchId, $request->query('category'), $request->query('supplier'), $request->query('product'));

        return view('reports.stock.index', compact(
            'from', 'to', 'branchId', 'branches', 'categories', 'suppliers', 'data',
            'selectedCategory', 'selectedSupplier', 'selectedProduct'
        ));
    }

    public function export(Request $request)
    {
        if (!app(\App\Services\PermissionService::class)->canAny(['export_stock_report', 'reports_export'])) {
            abort(403, 'You do not have permission to export the stock report.');
        }

        $branchId = $this->selectedBranch($request);
        $data = $this->compute($branchId, $request->query('category'), $request->query('supplier'), $request->query('product'));

        $filename = 'stock_report_' . now()->format('Ymd_His') . '.csv';

        $rows = $data['rows']->map(fn ($r) => [
            $r['product']->name ?? '',
            $r['product']->sku ?? '',
            $r['product']->category->name ?? '',
            $r['branch_name'],
            (int) $r['total_stock'],
            (int) $r['sold_qty'],
            (int) $r['remaining'],
            $this->moneyExport($r['value']),
        ]);

        $headers = ['Product', 'SKU', 'Category', 'Branch', 'In Stock', 'Sold', 'Remaining', 'Stock Value'];

        return $this->streamCsv($filename, $headers, $rows, fn ($r) => $r);
    }

    protected function compute(int $branchId, $category = null, $supplier = null, $product = null): array
    {
        $stock = Stock::query()->with(['product.category', 'branch', 'supplier']);

        if ($branchId > 0) {
            $stock->where('branch_id', $branchId);
        }

        if ($category) {
            $stock->whereHas('product', fn ($q) => $q->where('category_id', $category));
        }

        if ($supplier) {
            $stock->where('supplier_id', $supplier);
        }

        if ($product) {
            $stock->where('product_id', $product);
        }


        $stockRows = $stock->orderBy('product_id')->get();

        $rows = $stockRows->map(function (Stock $row)  {
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
        });

        $totals = [
            'products' => $rows->pluck('product')->filter()->unique('id')->count(),
            'in_stock' => (int) $rows->sum('total_stock'),
            'sold' => (int) $rows->sum('sold_qty'),
            'remaining' => (int) $rows->sum('remaining'),
            'value' => (float) $rows->sum('value'),
        ];

        return compact('rows', 'totals');
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
