<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\ContractWorkflowService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;

class ContractWorkflowController extends Controller
{
    protected PermissionService $permission;

    protected ContractWorkflowService $workflow;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
        $this->workflow = app(ContractWorkflowService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:contracts_edit'),
        ];
    }

    public function saveDraft(Contract $contract): RedirectResponse
    {
        $this->workflow->saveDraft($contract);

        return redirect()->route('contracts.show', $contract)->with('success', 'Contract saved as draft.');
    }

    public function submit(Contract $contract): RedirectResponse
    {
        $this->workflow->submitDraft($contract);

        return redirect()->route('contracts.show', $contract)->with('success', 'Contract submitted for workflow approval.');
    }

    public function sign(Request $request, Contract $contract): RedirectResponse
    {
        $this->workflow->sign($contract, auth()->user()->normalizedRole(), $request->validate([
            'notes' => ['nullable', 'string'],
            'signature_data' => ['nullable', 'string'],
            'signature_method' => ['nullable', 'string', 'max:50'],
        ]));

        return redirect()->route('contracts.show', $contract)->with('success', 'Signature recorded.');
    }

    public function reject(Request $request, Contract $contract): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $this->workflow->reject($contract, auth()->user()->normalizedRole(), (string) ($data['notes'] ?? ''));

        return redirect()->route('contracts.show', $contract)->with('success', 'Contract rejected.');
    }

    public function finalize(Request $request, Contract $contract): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $this->workflow->finalise($contract, auth()->user()->normalizedRole(), (string) ($data['notes'] ?? ''));

        return redirect()->route('contracts.show', $contract)->with('success', 'Contract finalised successfully.');
    }
}
