<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Branch;
use App\Models\Supplier;
use App\Services\BranchAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:manage_suppliers'),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $branchFilter = trim((string) $request->query('branch', ''));
        $typeFilter = trim((string) $request->query('type', ''));

        $base = app(BranchAccess::class)->scope(Supplier::query());
        $query = (clone $base)->with(['branch', 'member'])->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('contact_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if ($branchFilter !== '') {
            $query->where('branch_id', $branchFilter);
        }

        if ($typeFilter !== '') {
            $query->where('supplier_type', $typeFilter);
        }

        $suppliers = $query->paginate(20)->withQueryString();
        $branches = app(BranchAccess::class)->branches()->get();

        $stats = [
            'total' => (clone $base)->count(),
            'members' => (clone $base)->where('supplier_type', 'member')->count(),
            'non_members' => (clone $base)->where('supplier_type', 'non_member')->count(),
        ];

        return view('suppliers.index', compact(
            'suppliers',
            'branches',
            'search',
            'branchFilter',
            'typeFilter',
            'stats'
        ));
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        app(BranchAccess::class)->authorize($request->validated('branch_id'));
        Supplier::create($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier added successfully!');
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        app(BranchAccess::class)->authorize($supplier->branch_id);
        app(BranchAccess::class)->authorize($request->validated('branch_id'));
        $supplier->update($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier updated successfully!');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        app(BranchAccess::class)->authorize($supplier->branch_id);
        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully!');
    }
}
