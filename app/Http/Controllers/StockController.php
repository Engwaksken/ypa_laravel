<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class StockController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:manage_stock'))->only(['index']),
            (new Middleware('permission:stock_create'))->only(['store']),
            (new Middleware('permission:stock_edit'))->only(['update']),
            (new Middleware('permission:stock_delete'))->only(['destroy']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $branchFilter = trim((string) $request->query('branch', ''));
        $supplierFilter = trim((string) $request->query('supplier', ''));
        $alertFilter = trim((string) $request->query('alert', ''));

        $query = Stock::query()->with(['product', 'supplier', 'branch'])->orderByDesc('id');

        if ($search !== '') {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        if ($branchFilter !== '') {
            $query->where('branch_id', $branchFilter);
        }

        if ($supplierFilter !== '') {
            $query->where('supplier_id', $supplierFilter);
        }

        if ($alertFilter === 'low') {
            $query->whereColumn('quantity', '<=', 'total_stock')->where('quantity', '<=', 10);
        } elseif ($alertFilter === 'expiry') {
            $query->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30)->toDateString());
        }

        $stock = $query->paginate(20)->withQueryString();

        $products = Product::query()->where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::query()->orderBy('name')->get();
        $branches = Branch::query()->orderBy('name')->get();

        $stats = [
            'lines' => Stock::count(),
            'units' => (int) Stock::sum('quantity'),
            'low' => Stock::where('quantity', '<=', 10)->count(),
            'expiring' => Stock::whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30)->toDateString())
                ->count(),
        ];

        return view('stock.index', compact(
            'stock',
            'products',
            'suppliers',
            'branches',
            'search',
            'branchFilter',
            'supplierFilter',
            'alertFilter',
            'stats'
        ));
    }

    public function store(StockRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $existing = Stock::query()
            ->where('product_id', $data['product_id'])
            ->where('branch_id', $data['branch_id'])
            ->first();

        if ($existing) {
            $existing->quantity += (int) $data['quantity'];
            $existing->total_stock += (int) $data['quantity'];
            $existing->fill($request->safe()->except(['quantity']));
            $existing->save();

            return redirect()
                ->route('stock.index')
                ->with('success', 'Stock updated successfully!');
        }

        $data['total_stock'] = (int) $data['quantity'];
        Stock::create($data);

        return redirect()
            ->route('stock.index')
            ->with('success', 'Stock added successfully!');
    }

    public function update(StockRequest $request, Stock $stock): RedirectResponse
    {
        $data = $request->validated();
        $data['total_stock'] = max((int) $stock->total_stock, (int) $data['quantity']);

        $stock->update($data);

        return redirect()
            ->route('stock.index')
            ->with('success', 'Stock updated successfully!');
    }

    public function destroy(Stock $stock): RedirectResponse
    {
        $stock->delete();

        return redirect()
            ->route('stock.index')
            ->with('success', 'Stock entry deleted successfully!');
    }
}
