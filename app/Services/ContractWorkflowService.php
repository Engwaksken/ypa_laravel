<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractApproval;
use Illuminate\Support\Facades\DB;

class ContractWorkflowService
{
    public const STEP_DRAFT = 0;
    public const STEP_OFFICER_SIGNED = 1;
    public const STEP_ACCOUNTANT_SIGNED = 2;
    public const STEP_DIRECTOR_SIGNED = 3;
    public const STEP_FINALISED = 4;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_OFFICER = 'pending_officer';
    public const STATUS_PENDING_ACCOUNTANT = 'pending_accountant';
    public const STATUS_PENDING_DIRECTOR = 'pending_director';
    public const STATUS_FINALISING = 'finalising';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const ROLE_OFFICER = 'officer';
    public const ROLE_ACCOUNTANT = 'accountant';
    public const ROLE_DIRECTOR = 'director';

    public function submitDraft(Contract $contract): Contract
    {
        $contract->forceFill([
            'workflow_step' => self::STEP_DRAFT,
            'workflow_status' => self::STATUS_PENDING_OFFICER,
        ])->save();

        return $contract->refresh();
    }

    public function saveDraft(Contract $contract): Contract
    {
        $contract->forceFill([
            'workflow_step' => self::STEP_DRAFT,
            'workflow_status' => self::STATUS_DRAFT,
        ])->save();

        return $contract->refresh();
    }

    public function sign(Contract $contract, string $role, array $payload = []): Contract
    {
        $currentStep = (int) ($contract->workflow_step ?? self::STEP_DRAFT);
        $expectedRole = [
            self::STEP_DRAFT => self::ROLE_OFFICER,
            self::STEP_OFFICER_SIGNED => self::ROLE_ACCOUNTANT,
            self::STEP_ACCOUNTANT_SIGNED => self::ROLE_DIRECTOR,
        ][$currentStep] ?? null;

        $actorRole = $this->normalizeRole($role);
        if (!in_array($actorRole, ['admin', 'manager'], true) && $expectedRole !== $actorRole) {
            abort(403, 'You are not authorised to sign this workflow step.');
        }

        $nextMap = [
            self::STEP_DRAFT => [self::STEP_OFFICER_SIGNED, self::STATUS_PENDING_ACCOUNTANT],
            self::STEP_OFFICER_SIGNED => [self::STEP_ACCOUNTANT_SIGNED, self::STATUS_PENDING_DIRECTOR],
            self::STEP_ACCOUNTANT_SIGNED => [self::STEP_DIRECTOR_SIGNED, self::STATUS_FINALISING],
        ];

        if (!isset($nextMap[$currentStep])) {
            abort(422, 'All signatures are already complete.');
        }

        [$nextStep, $nextStatus] = $nextMap[$currentStep];

        DB::transaction(function () use ($contract, $currentStep, $role, $payload, $nextStep, $nextStatus): void {
            ContractApproval::query()->create([
                'contract_id' => $contract->id,
                'step' => $currentStep,
                'role' => $this->normalizeRole($role),
                'user_id' => auth()->id(),
                'action' => 'signed',
                'notes' => (string) ($payload['notes'] ?? ''),
                'signature_data' => (string) ($payload['signature_data'] ?? ''),
                'signature_method' => (string) ($payload['signature_method'] ?? 'typed'),
                'ip_address' => substr((string) request()->ip(), 0, 45),
            ]);

            $contract->forceFill([
                'workflow_step' => $nextStep,
                'workflow_status' => $nextStatus,
            ])->save();
        });

        return $contract->refresh();
    }

    public function reject(Contract $contract, string $role, string $notes = ''): Contract
    {
        $actorRole = $this->normalizeRole($role);
        if (!in_array($actorRole, [self::ROLE_ACCOUNTANT, self::ROLE_DIRECTOR, 'admin', 'manager'], true)) {
            abort(403, 'You are not authorised to reject contracts.');
        }

        DB::transaction(function () use ($contract, $actorRole, $notes): void {
            ContractApproval::query()->create([
                'contract_id' => $contract->id,
                'step' => (int) ($contract->workflow_step ?? self::STEP_DRAFT),
                'role' => $actorRole,
                'user_id' => auth()->id(),
                'action' => 'rejected',
                'notes' => $notes,
                'ip_address' => substr((string) request()->ip(), 0, 45),
            ]);

            $contract->forceFill([
                'workflow_status' => self::STATUS_REJECTED,
            ])->save();
        });

        return $contract->refresh();
    }

    public function finalise(Contract $contract, string $role = 'officer', string $notes = ''): Contract
    {
        $actorRole = $this->normalizeRole($role);
        if (!in_array($actorRole, [self::ROLE_OFFICER, self::ROLE_ACCOUNTANT, 'admin', 'manager'], true)) {
            abort(403, 'You are not authorised to finalise contracts.');
        }

        if ((string) ($contract->workflow_status ?? '') !== self::STATUS_FINALISING || (int) ($contract->workflow_step ?? self::STEP_DRAFT) < self::STEP_DIRECTOR_SIGNED) {
            abort(422, 'Contract is not ready for finalisation.');
        }

        DB::transaction(function () use ($contract, $actorRole, $notes): void {
            ContractApproval::query()->create([
                'contract_id' => $contract->id,
                'step' => self::STEP_FINALISED,
                'role' => $actorRole,
                'user_id' => auth()->id(),
                'action' => 'finalised',
                'notes' => $notes ?: 'Contract finalised and activated.',
                'ip_address' => substr((string) request()->ip(), 0, 45),
            ]);

            $contract->forceFill([
                'workflow_step' => self::STEP_FINALISED,
                'workflow_status' => self::STATUS_APPROVED,
                'status' => 'ACTIVE',
                'finalised_at' => now(),
            ])->save();
        });

        return $contract->refresh();
    }

    public function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));

        return match ($role) {
            'superadmin', 'super_admin', 'supper_admin' => 'supper_admin',
            default => $role,
        };
    }
}
