<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractTerminationRequest;
use App\Models\Contract;
use App\Models\ContractTermination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TerminationController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:termination'),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));

        $query = ContractTermination::query()
            ->with(['contract.member', 'contract.group', 'creator'])
            ->orderByDesc('termination_date')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('reason', 'like', $like)
                    ->orWhereHas('contract', function ($cq) use ($like): void {
                        $cq->where('contract_number', 'like', $like);
                    })
                    ->orWhereHas('contract.member', function ($mq) use ($like): void {
                        $mq->whereRaw("TRIM(CONCAT_WS(' ', COALESCE(first_name,''), COALESCE(last_name,''), COALESCE(other_name,''))) LIKE ?", [$like]);
                    })
                    ->orWhereHas('contract.group', function ($gq) use ($like): void {
                        $gq->where('group_name', 'like', $like)
                            ->orWhere('group_code', 'like', $like);
                    });
            });
        }

        $terminations = $query->paginate($perPage)->withQueryString();

        return view('termination.index', compact('terminations', 'search', 'perPage'));
    }

    public function create(Request $request): View
    {
        $contractId = (int) $request->query('contract_id', 0);
        $contract = $contractId > 0
            ? Contract::query()->with(['member', 'group', 'project', 'items'])->find($contractId)
            : null;

        return view('termination.create', [
            'contract' => $contract,
            'contracts' => Contract::query()->with(['member', 'group', 'terminations'])->where('status', 'ACTIVE')->whereDoesntHave('terminations')->orderByDesc('id')->limit(200)->get(),
        ]);
    }

    public function store(ContractTerminationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = (int) auth()->id();

        $termination = DB::transaction(function () use ($data, $userId): ContractTermination {
            $contract = Contract::query()->lockForUpdate()->with(['member', 'group', 'items'])->findOrFail((int) $data['contract_id']);

            if (strtoupper((string) $contract->status) !== 'ACTIVE') {
                throw ValidationException::withMessages(['contract_id' => 'Only active contracts can be terminated.']);
            }

            if (ContractTermination::query()->where('contract_id', $contract->id)->exists()) {
                throw ValidationException::withMessages(['contract_id' => 'This contract already has a termination record.']);
            }

            $contractAmount = (float) ($contract->contract_amount ?? $contract->total_amount ?? 0);
            // amount_paid is derived server-side from the contract's authoritative paid
            // cache (kept in sync with APPROVED payment transactions by
            // PaymentController::recalculateContractTotals()). User-supplied
            // amount_paid / deduction_base are ignored — matches the legacy app.
            $amountPaid = (float) ($contract->amount_paid ?? 0);
            $projectProjection = (float) ($data['project_projection'] ?? $this->projectProjection($contract));
            $deductionBase = $amountPaid > 0 ? $amountPaid : ($projectProjection > 0 ? $projectProjection : $contractAmount);
            $deductionRate = (float) ($data['deduction_rate'] ?? 50);

            if (!array_key_exists('deduction_amount', $data) || (float) $data['deduction_amount'] <= 0) {
                $deductionAmount = round($deductionBase * ($deductionRate / 100), 2);
            } else {
                $deductionAmount = (float) $data['deduction_amount'];
            }

            $refundAmount = array_key_exists('refund_amount', $data) && (float) $data['refund_amount'] > 0
                ? (float) $data['refund_amount']
                : round(max(0, $deductionBase - $deductionAmount), 2);

            if ($deductionAmount > $deductionBase) {
                throw ValidationException::withMessages(['deduction_amount' => 'Deduction amount cannot exceed the deduction base.']);
            }

            if ($refundAmount > round($deductionBase - $deductionAmount, 2)) {
                throw ValidationException::withMessages(['refund_amount' => 'Refund amount cannot exceed the deduction base minus the deduction amount.']);
            }

            $termination = ContractTermination::create([
                'contract_id' => $contract->id,
                'termination_date' => $data['termination_date'],
                'reason' => $data['reason'],
                'amount_paid' => $amountPaid,
                'contract_amount' => $contractAmount,
                'project_projection' => $projectProjection,
                'deduction_base' => round($deductionBase, 2),
                'deduction_base_source' => $amountPaid > 0 ? 'amount_paid' : ($projectProjection > 0 ? 'project_projection' : 'contract_amount'),
                'deduction_rate' => $deductionRate,
                'deduction_amount' => $deductionAmount,
                'refund_amount' => $refundAmount,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $contract->update([
                'status' => 'TERMINATED',
                'workflow_status' => 'approved',
                'updated_by' => $userId,
            ]);

            return $termination;
        });

        return redirect()->route('termination.show', $termination)->with('success', 'Contract terminated successfully.');
    }

    public function show(ContractTermination $termination): View
    {
        $termination->load(['contract.member', 'contract.group', 'creator']);

        return view('termination.show', compact('termination'));
    }

    protected function projectProjection(Contract $contract): float
    {
        $total = 0.0;

        foreach ($contract->items as $item) {
            $total += max(
                (float) ($item->projected_harvest_balance ?? 0),
                (float) ($item->projected_harvest_amount ?? 0),
                (float) ($item->amount_at_maturity ?? 0),
                (float) ($item->amount_payable ?? 0),
                (float) ($item->net_payable ?? 0),
                (float) ($item->balance_amount ?? 0),
                (float) ($item->total_price ?? 0)
            );
        }

        return round($total, 2);
    }
}
