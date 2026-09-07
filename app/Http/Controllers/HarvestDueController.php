<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHarvestRequest;
use App\Models\ContractItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class HarvestDueController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            (new Middleware('permission:harvest_due'))->only(['index']),
            (new Middleware('permission:harvest_due_full'))->only(['record']),
            (new Middleware('throttle:30,1'))->only(['record']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));

        $query = ContractItem::query()
            ->with(['contract.member', 'contract.group', 'contract.project'])
            ->whereHas('contract', function ($q): void {
                $q->whereNotIn('status', ['DRAFT', 'TERMINATED', 'CANCELLED', 'DEFAULTED']);
            })
            ->where(function ($q): void {
                $q->where('balance_amount', '>', 0)
                    ->orWhere('projected_harvest_balance', '>', 0)
                    ->orWhere('harvest_amount', '>', 0)
                    ->orWhere('balance_quantity', '>', 0)
                    ->orWhere('remaining_goats', '>', 0)
                    ->orWhere('remaining_hives', '>', 0)
                    ->orWhere('remaining_acreages', '>', 0);
            })
            ->whereNotIn('status', ['cancelled', 'canceled', 'terminated', 'ended', 'closed', 'completed'])
            ->orderBy('contract_id')
            ->orderBy('item_order')
            ->orderBy('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('item_name', 'like', $like)
                    ->orWhere('item_type', 'like', $like)
                    ->orWhere('project_name', 'like', $like)
                    ->orWhereHas('contract', function ($cq) use ($like): void {
                        $cq->where('contract_number', 'like', $like);
                    })
                    ->orWhereHas('contract.member', function ($mq) use ($like): void {
                        $mq->whereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
                    })
                    ->orWhereHas('contract.group', function ($gq) use ($like): void {
                        $gq->where('group_name', 'like', $like)->orWhere('group_code', 'like', $like);
                    });
            });
        }

        $items = $query->paginate($perPage)->withQueryString();

        return view('harvest_due.index', compact('items', 'search', 'perPage'));
    }

    public function record(StoreHarvestRequest $request, ContractItem $contractItem, HarvestController $harvestController): RedirectResponse
    {
        $data = $request->validated();
        $data['contract_id'] = $contractItem->contract_id;
        $data['contract_item_id'] = $contractItem->id;

        $harvest = \Illuminate\Support\Facades\DB::transaction(fn () => $harvestController->createHarvest($data, (int) auth()->id()));

        return redirect()->route('harvests.show', $harvest)->with('success', 'Harvest request recorded successfully.');
    }
}
