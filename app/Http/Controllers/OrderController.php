<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Stock;
use App\Services\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:manage_orders'),
            (new Middleware('throttle:30,1'))->only(['updateStatus']),
        ];
    }

    public function index(Request $request): View
    {
        $base = app(BranchAccess::class)->scope(Order::query());
        $query = (clone $base)->with(['branch', 'items'])->latest('id');
        if ($request->filled('search')) {
            $search = '%' . trim($request->string('search')) . '%';
            $query->where(fn ($q) => $q->where('order_number', 'like', $search)
                ->orWhere('customer_name', 'like', $search)
                ->orWhere('phone', 'like', $search));
        }
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        $orders = $query->paginate(20)->withQueryString();
        $stats = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'processing' => (clone $base)->where('status', 'processing')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'revenue' => (clone $base)->where('status', 'completed')->sum('total_amount'),
        ];
        return view('orders.index', compact('orders', 'stats'));
    }

    public function show(Order $order): View
    {
        app(BranchAccess::class)->authorize($order->branch_id);
        $order->load(['branch', 'items.product', 'customer']);
        return view('orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        app(BranchAccess::class)->authorize($order->branch_id);
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'processing', 'completed', 'cancelled'])]]);
        $transitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];
        $changed = DB::transaction(function () use ($order, $data, $transitions) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            app(BranchAccess::class)->authorize($locked->branch_id);
            if (!in_array($data['status'], $transitions[$locked->status] ?? [], true)) {
                return false;
            }
            if ($data['status'] === 'cancelled') {
                // Only release explicitly tracked reservations. Historical
                // orders may already have had inventory reconciled externally.
                $reservations = DB::table('order_stock_reservations')
                    ->where('order_id', $locked->id)->whereNull('released_at')
                    ->orderBy('stock_id')->lockForUpdate()->get();
                foreach ($reservations as $reservation) {
                    $stock = Stock::query()->whereKey($reservation->stock_id)->lockForUpdate()->first();
                    abort_if(!$stock, 422, 'Reserved stock is missing; reconcile inventory before cancelling this order.');
                    $stock->increment('quantity', $reservation->quantity);
                    DB::table('order_stock_reservations')->where('id', $reservation->id)
                        ->update(['released_at' => now()]);
                }
            }
            $locked->update(['status' => $data['status']]);
            $order->status = $locked->status;
            return true;
        }, 3);
        if (!$changed) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Invalid order status transition.'], 422)
                : back()->with('error', 'Invalid order status transition.');
        }
        return $request->expectsJson()
            ? response()->json(['success' => true, 'status' => $order->status])
            : back()->with('success', 'Order status updated.');
    }
}
