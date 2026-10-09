<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Services\BranchAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
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

        $base = app(BranchAccess::class)->scope(Stock::query());
        $query = (clone $base)->with(['product', 'supplier', 'branch'])->orderByDesc('id');

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
        $suppliers = app(BranchAccess::class)->scope(Supplier::query())->orderBy('name')->get();
        $branches = app(BranchAccess::class)->branches()->get();

        $stats = [
            'lines' => (clone $base)->count(),
            'units' => (int) (clone $base)->sum('quantity'),
            'low' => (clone $base)->where('quantity', '<=', 10)->count(),
            'expiring' => (clone $base)->whereNotNull('expiry_date')
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
        app(BranchAccess::class)->authorize($data['branch_id']);
        if (!empty($data['supplier_id'])) {
            app(BranchAccess::class)->authorize(Supplier::findOrFail($data['supplier_id'])->branch_id);
        }

        DB::transaction(function () use ($data, $request) {
            // Serialize additions for the same branch, including the first row.
            Branch::query()->whereKey($data['branch_id'])->lockForUpdate()->firstOrFail();
            $existing = Stock::query()
                ->where('product_id', $data['product_id'])
                ->where('branch_id', $data['branch_id'])
                ->orderBy('id')->lockForUpdate()->first();

            if ($existing) {
                $existing->quantity += (int) $data['quantity'];
                $existing->total_stock += (int) $data['quantity'];
                $existing->fill($request->safe()->except(['quantity']));
                $existing->save();

                return;
            }

            $data['total_stock'] = (int) $data['quantity'];
            Stock::create($data);
        }, 3);

        return redirect()
            ->route('stock.index')
            ->with('success', 'Stock added successfully!');
    }

    public function update(StockRequest $request, Stock $stock): RedirectResponse
    {
        $data = $request->validated();
        app(BranchAccess::class)->authorize($stock->branch_id);
        app(BranchAccess::class)->authorize($data['branch_id']);
        if (!empty($data['supplier_id'])) {
            app(BranchAccess::class)->authorize(Supplier::findOrFail($data['supplier_id'])->branch_id);
        }
        DB::transaction(function () use ($stock, $data) {
            $locked = Stock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
            app(BranchAccess::class)->authorize($locked->branch_id);
            if ((int) $locked->product_id !== (int) $data['product_id'] || (int) $locked->branch_id !== (int) $data['branch_id']) {
                abort_if($this->hasPendingReservations($locked), 422, 'Stock reserved by orders cannot change product or branch.');
            }
            $data['total_stock'] = max((int) $locked->total_stock, (int) $data['quantity']);
            $locked->update($data);
        }, 3);

        return redirect()
            ->route('stock.index')
            ->with('success', 'Stock updated successfully!');
    }

    public function destroy(Stock $stock): RedirectResponse
    {
        app(BranchAccess::class)->authorize($stock->branch_id);
        DB::transaction(function () use ($stock) {
            $locked = Stock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
            app(BranchAccess::class)->authorize($locked->branch_id);
            abort_if($this->hasPendingReservations($locked), 422, 'Stock reserved by pending orders cannot be deleted.');
            $locked->delete();
        }, 3);

        return redirect()
            ->route('stock.index')
            ->with('success', 'Stock entry deleted successfully!');
    }

    private function hasPendingReservations(Stock $stock): bool
    {
        return DB::table('order_stock_reservations as reservations')
            ->join('orders', 'orders.id', '=', 'reservations.order_id')
            ->where('reservations.stock_id', $stock->id)->whereNull('reservations.released_at')
            ->whereIn('orders.status', ['pending', 'processing'])->exists();
    }
}
