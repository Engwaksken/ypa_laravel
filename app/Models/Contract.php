<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends Model
{
    protected $table = 'contracts';

    protected $fillable = [
        'contract_number',
        'member_id',
        'group_id',
        'project_id',
        'branch_id',
        'project_type_id',
        'project_category_id',
        'management_model',
        'project_code',
        'receipt_number',
        'admin_discount',
        'admin_offers',
        'investment_period',
        'contract_period_years',
        'contract_amount',
        'contract_amount_paid',
        'contract_expiration',
        'amount_paid',
        'duration_months',
        'outstanding_balance',
        'bee_sub_type',
        'hives',
        'goats_purchased',
        'goats_offers',
        'purchase_fee',
        'purchase_discount',
        'monthly_return',
        'total_amount',
        'total_payable',
        'total_paid',
        'total_outstanding',
        'projected_harvest_amount',
        'projected_harvest_balance',
        'monthly_payout_amount',
        'expected_quantity',
        'expected_units',
        'amount_in_words',
        'preparation_months',
        'contract_duration_years',
        'contract_file',
        'template_path',
        'contract_for',
        'duration',
        'payment_frequency',
        'signing_date',
        'start_date',
        'end_date',
        'director_name',
        'director_signature',
        'farmer_photo',
        'nok_photo',
        'country',
        'region',
        'district',
        'subcounty',
        'parish',
        'village',
        'payment_method_id',
        'payment_reference',
        'payment_date',
        'received_by',
        'payment_status',
        'is_partial_payment',
        'is_personal_management',
        'guardian_photo',
        'kid_photo',
        'status',
        'workflow_status',
        'workflow_step',
        'finalised_at',
        'notes',
        'created_by',
        'updated_by',
        'officer_signature',
        'accountant_signature',
        'membership_already_paid',
        'contract_outstanding',
    ];

    public const STATUSES = ['DRAFT', 'ACTIVE', 'COMPLETED', 'TERMINATED', 'DEFAULTED', 'CANCELLED'];

    public const PAYMENT_FREQUENCIES = ['MONTHLY', 'QUARTERLY', 'SEMI_ANNUAL', 'ANNUAL', 'CUSTOM'];

    public const WORKFLOW_STATUSES = ['draft', 'pending_officer', 'pending_accountant', 'pending_director', 'finalising', 'approved', 'rejected'];

    protected $casts = [
        'contract_number' => 'string',
        'contract_for' => 'string',
        'project_code' => 'string',
        'template_path' => 'string',
        'payment_frequency' => 'string',
        'status' => 'string',
        'workflow_status' => 'string',
        'member_id' => 'integer',
        'group_id' => 'integer',
        'project_id' => 'integer',
        'branch_id' => 'integer',
        'project_type_id' => 'integer',
        'project_category_id' => 'integer',
        'payment_method_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'workflow_step' => 'integer',
        'duration' => 'integer',
        'duration_months' => 'integer',
        'contract_amount' => 'decimal:2',
        'contract_amount_paid' => 'decimal:2',
        'contract_expiration' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'contract_outstanding' => 'decimal:2',
        'membership_already_paid' => 'decimal:2',
        'signing_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'finalised_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    public function projectData(): HasOne
    {
        return $this->hasOne(ContractProjectData::class, 'contract_id');
    }

    public function projectDataBackup(): HasOne
    {
        return $this->hasOne(ContractProjectDataBackup::class, 'contract_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ContractApproval::class, 'contract_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ContractAttachment::class, 'contract_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ContractNotification::class, 'contract_id');
    }

    public function terminations(): HasMany
    {
        return $this->hasMany(ContractTermination::class, 'contract_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ContractSchedule::class, 'contract_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(ContractPayout::class, 'contract_id');
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(ContractHarvest::class, 'contract_id');
    }

    public function itemHarvests(): HasMany
    {
        return $this->hasMany(ContractItemHarvest::class, 'contract_id');
    }

    public function getEffectiveStatusAttribute(): string
    {
        $workflowStatus = strtolower(trim((string) ($this->workflow_status ?? '')));

        return $workflowStatus !== '' && $workflowStatus !== 'draft'
            ? $workflowStatus
            : strtoupper((string) ($this->status ?? 'DRAFT'));
    }

    public function isDraft(): bool
    {
        return strtolower((string) ($this->workflow_status ?? '')) === 'draft';
    }

    public function isApproved(): bool
    {
        return strtolower((string) ($this->workflow_status ?? '')) === 'approved' || strtoupper((string) ($this->status ?? '')) === 'ACTIVE';
    }
}
