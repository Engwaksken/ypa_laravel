<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuestOrderRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class GuestOrderController extends Controller
{
    public static function middleware(): array
    {
        return [(new Middleware('throttle:10,1'))->only(['store'])];
    }

    public function create(): View
    {
        return view('orders.place', [
            'branches' => Branch::query()->where('status', true)->orderBy('name')->get(),
            'products' => Product::query()->with(['category'])->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreGuestOrderRequest $request, DatabaseManager $db): JsonResponse
    {
        $data = $request->validated();
        $order = $db->transaction(function () use ($data) {
            $branch = Branch::findOrFail($data['branch_id']);
            $customer = Customer::query()->where('phone', $data['phone'])->orWhere('email', $data['email'] ?? '__none__')->first();
            $customer ??= Customer::create([
                'branch_id' => $branch->id, 'name' => $data['customer_name'],
                'phone' => $data['phone'], 'email' => $data['email'] ?? null,
                'address' => $data['delivery_location'], 'customer_type' => 'non_member',
            ]);
            $items = [];
            $total = 0;
            foreach ($data['items'] as $item) {
                $product = Product::query()->whereKey($item['id'])->where('is_active', true)->firstOrFail();
                $stock = $product->stock()->where('branch_id', $branch->id)->lockForUpdate()->first();
                abort_if(!$stock || $stock->quantity < $item['quantity'], 422, 'Insufficient stock.');
                $price = (float) $stock->selling_price;
                $subtotal = $price * $item['quantity'];
                $items[] = compact('product', 'stock', 'subtotal') + ['quantity' => $item['quantity'], 'price' => $price];
                $total += $subtotal;
            }
            $order = Order::create([
                'order_number' => 'ORD-' . now()->format('Ymd') . '-' . str_pad((string) (Order::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id, 'customer_name' => $data['customer_name'],
                'phone' => $data['phone'], 'email' => $data['email'] ?? null,
                'delivery_location' => $data['delivery_location'], 'branch_id' => $branch->id,
                'payment_method' => $data['payment_method'], 'total_amount' => $total,
                'orders_count' => Order::where('phone', $data['phone'])->count() + 1,
                'status' => 'pending', 'notes' => $data['notes'] ?? null, 'is_guest_order' => true,
            ]);
            foreach ($items as $item) {
                $order->items()->create(['product_id' => $item['product']->id, 'product_name' => $item['product']->name, 'sku' => $item['product']->sku, 'quantity' => $item['quantity'], 'price' => $item['price'], 'subtotal' => $item['subtotal']]);
                $item['stock']->decrement('quantity', $item['quantity']);
            }
            return $order;
        });
        return response()->json(['success' => true, 'order_number' => $order->order_number, 'total' => $order->total_amount]);
    }
}
