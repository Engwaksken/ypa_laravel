<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;

class CategoryController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:products_create'))->only(['store']),
            (new Middleware('permission:products_edit'))->only(['update']),
            (new Middleware('permission:products_delete'))->only(['destroy']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Category::create($data);

        return redirect()
            ->route('products.index', ['tab' => 'categories'])
            ->with('success', 'Category added successfully!');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return redirect()
            ->route('products.index', ['tab' => 'categories'])
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'This category has products and cannot be deleted.');
        }

        $category->delete();

        return redirect()
            ->route('products.index', ['tab' => 'categories'])
            ->with('success', 'Category deleted successfully!');
    }
}
