<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpiryStockController extends ReportController
{
    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('auth'),
            new \Illuminate\Routing\Controllers\Middleware('user.status'),
            new \Illuminate\Routing\Controllers\Middleware('permission:view_expiry_stock_report'),
            (new \Illuminate\Routing\Controllers\Middleware('permission:export_expiry_stock_report'))->only(['export']),
        ];
    }

    public function index(Request $request)
    {
        $branchId = $this->selectedBranch($request);
        $branches = $this->branches();
        $categories = Category::orderBy('category_name')->get(['id', 'category_name']);

        $days = max(1, (int) $request->query('days', 30));
        $selectedCategory = $request->query('category');
        $selectedStatus = $request->query('status');

        $data = $this->compute($branchId, $days, $request->query('category'), $request->query('status'));

        return view('reports.expiry-stock.index', compact(
            'branchId', 'branches', 'categories', 'days', 'data', 'selectedCategory', 'selectedStatus'
        ));
    }

    public function export(Request $request)
    {
        if (!app(\App\Services\PermissionService::class)->canAny(['export_expiry_stock_report', 'reports_export'])) {
            abort(403, 'You do not have permission to export the expiry stock report.');
        }

        $branchId = $this->selectedBranch($request);
        $days = max(1, (int) $request->query('days', 30));
        $data = $this->compute($branchId, $days, $request->query('category'), $request->query('status'));

        $filename = 'expiry_stock_report_' . $days . '_' . now()->format('Ymd_His') . '.csv';

        $rows = $data['rows']->map(fn ($r) => [
            $r['product']->name ?? '',
            $r['product']->sku ?? '',
            $r['product']->category->name ?? '',
            $r['branch_name'],
            $r['expiry_date']?->format('Y-m-d') ?? '-',
            $r['days_remaining'],
            $r['status'],
            (int) $r['quantity'],
            $this->moneyExport($r['value']),
        ]);

        $headers = ['Product', 'SKU', 'Category', 'Branch', 'Expiry Date', 'Days Left', 'Status', 'Qty', 'Value'];

        return $this->streamCsv($filename, $headers, $rows, fn ($r) => $r);
    }

    protected function compute(int $branchId, int $days, $category = null, $status = null): array
    {
        $stock = Stock::query()->with(['product.category', 'branch'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days)->toDateString());

        if ($branchId > 0) {
            $stock->where('branch_id', $branchId);
        }

        if ($category) {
            $stock->whereHas('product', fn ($q) => $q->where('category_id', $category));
        }

        $stockRows = $stock->orderBy('expiry_date')->get();

        $rows = $stockRows->map(function (Stock $row) use ($status) {
            $expiry = $row->expiry_date instanceof Carbon
                ? $row->expiry_date
                : Carbon::parse($row->expiry_date);
            $daysRemaining = (int) now()->startOfDay()->diffInDays($expiry->copy()->startOfDay(), false);

            if ($daysRemaining < 0) {
                $itemStatus = 'Expired';
            } elseif ($daysRemaining <= 7) {
                $itemStatus = 'Critical';
            } else {
                $itemStatus = 'Warning';
            }

            if ($status && $status !== $itemStatus) {
                return null;
            }

            return [
                'product' => $row->product,
                'branch_name' => $row->branch->name ?? '-',
                'expiry_date' => $expiry,
                'days_remaining' => $daysRemaining,
                'status' => $itemStatus,
                'quantity' => (int) $row->quantity,
                'value' => (int) $row->quantity * (float) $row->cost_price,
            ];
        })->filter()->values();

        $counts = [
            'expired' => $rows->where('status', 'Expired')->count(),
            'critical' => $rows->where('status', 'Critical')->count(),
            'warning' => $rows->where('status', 'Warning')->count(),
        ];
        $counts['total'] = $counts['expired'] + $counts['critical'] + $counts['warning'];

        return compact('rows', 'counts', 'days');
    }
}
