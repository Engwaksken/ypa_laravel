<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

class BranchAccess
{
    public function canAccessAll(): bool
    {
        return app(PermissionService::class)->canAny(['all_branches', 'view_all_branches']);
    }

    public function scope(Builder $query): Builder
    {
        if (!$this->canAccessAll()) {
            // An unassigned account must never silently become all-branch.
            $query->where('branch_id', $this->assignedBranchId());
        }
        return $query;
    }

    public function authorize(int|string|null $branchId): void
    {
        abort_unless($this->canAccessAll() || ($branchId !== null
            && auth()->user()?->branch_id !== null
            && (int) $branchId > 0
            && (int) $branchId === (int) auth()->user()->branch_id), 403, 'This branch is outside your access.');
    }

    public function branches(): Builder
    {
        $query = Branch::query()->orderBy('name');
        if (!$this->canAccessAll()) {
            $query->whereKey($this->assignedBranchId());
        }
        return $query;
    }

    private function assignedBranchId(): int
    {
        $branchId = (int) auth()->user()?->branch_id;
        abort_unless($branchId > 0, 403, 'Your account has no assigned branch.');
        return $branchId;
    }
}
