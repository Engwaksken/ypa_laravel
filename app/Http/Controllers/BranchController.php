<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BranchController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:manage_branches'),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = Branch::query()->orderBy('name');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('location', 'like', $like);
            });
        }

        $branches = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Branch::count(),
            'active' => Branch::where('status', true)->count(),
            'inactive' => Branch::where('status', false)->count(),
        ];

        return view('branches.index', compact('branches', 'search', 'stats'));
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $request->boolean('status', true);

        Branch::create($data);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch added successfully!');
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $request->boolean('status');

        $branch->update($data);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch updated successfully!');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->products()->exists() || $branch->users()->exists()) {
            return back()->with('error', 'This branch is in use and cannot be deleted.');
        }

        $branch->delete();

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch deleted successfully!');
    }
}
