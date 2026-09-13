<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
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
        $query = Order::query()->with(['branch', 'items'])->latest('id');
        if ($request->filled('search')) {
            $search = '%' . trim($request->string('search')) . '%';
            $query->where(fn ($q) => $q->where('order_number', 'like', $search)
                ->orWhere('customer_name', 'like', $search)
                ->orWhere('phone', 'like', $search));
        }
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        $orders = $query->paginate(20)->withQueryString();
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'revenue' => Order::where('status', 'completed')->sum('total_amount'),
        ];
        return view('orders.index', compact('orders', 'stats'));
    }

    public function show(Order $order): View
    {
        $order->load(['branch', 'items.product', 'customer']);
        return view('orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'processing', 'completed', 'cancelled'])]]);
        $transitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];
        if (!in_array($data['status'], $transitions[$order->status] ?? [], true)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Invalid order status transition.'], 422)
                : back()->with('error', 'Invalid order status transition.');
        }
        $order->update(['status' => $data['status']]);
        return $request->expectsJson()
            ? response()->json(['success' => true, 'status' => $order->status])
            : back()->with('success', 'Order status updated.');
    }
}
