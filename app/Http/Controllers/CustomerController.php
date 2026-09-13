<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:manage_customers'),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $branchFilter = trim((string) $request->query('branch', ''));
        $typeFilter = trim((string) $request->query('type', ''));

        $query = Customer::query()->with(['branch', 'member'])->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if ($branchFilter !== '') {
            $query->where('branch_id', $branchFilter);
        }

        if ($typeFilter !== '') {
            $query->where('customer_type', $typeFilter);
        }

        $customers = $query->paginate(20)->withQueryString();
        $branches = Branch::query()->orderBy('name')->get();

        $stats = [
            'total' => Customer::count(),
            'members' => Customer::where('customer_type', 'member')->count(),
            'non_members' => Customer::where('customer_type', 'non_member')->count(),
        ];

        return view('customers.index', compact(
            'customers',
            'branches',
            'search',
            'branchFilter',
            'typeFilter',
            'stats'
        ));
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer added successfully!');
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer updated successfully!');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted successfully!');
    }
}
