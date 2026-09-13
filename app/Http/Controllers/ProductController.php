<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ProductController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:manage_products'))->only(['index']),
            (new Middleware('permission:products_create'))->only(['store']),
            (new Middleware('permission:products_edit'))->only(['update']),
            (new Middleware('permission:products_delete'))->only(['destroy']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $categoryFilter = trim((string) $request->query('category', ''));
        $branchFilter = trim((string) $request->query('branch', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $activeTab = $request->query('tab', 'products');

        $query = Product::query()->with(['category', 'branch'])->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            });
        }

        if ($categoryFilter !== '') {
            $query->where('category_id', $categoryFilter);
        }

        if ($branchFilter !== '') {
            $query->where('branch_id', $branchFilter);
        }

        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $products = $query->paginate(20)->withQueryString();
        $categories = Category::query()->orderBy('name')->get();
        $branches = Branch::query()->orderBy('name')->get();

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'categories' => Category::count(),
        ];

        return view('products.index', compact(
            'products',
            'categories',
            'branches',
            'search',
            'categoryFilter',
            'branchFilter',
            'statusFilter',
            'activeTab',
            'stats'
        ));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product added successfully!');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }
}
