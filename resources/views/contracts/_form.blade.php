@php
    $contract = $contract ?? null;
    $contractFor = old('contract_for', optional($contract)->contract_for ?? 'member');
@endphp

@csrf
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Contract For</label>
        <select name="contract_for" class="form-select" required>
            <option value="member" @selected($contractFor === 'member')>Member</option>
            <option value="group" @selected($contractFor === 'group')>Group</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Member</label>
        <select name="member_id" class="form-select">
            <option value="">Select member</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" @selected((string) old('member_id', optional($contract)->member_id ?? '') === (string) $member->id)>{{ $member->full_name }} ({{ $member->membership_id }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select">
            <option value="">Select group</option>
            @foreach($groups as $group)
                <option value="{{ $group->id }}" @selected((string) old('group_id', optional($contract)->group_id ?? '') === (string) $group->id)>{{ $group->group_name }} ({{ $group->group_code }})</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Project</label>
        <select name="project_id" class="form-select" required>
            <option value="">Select project</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}" @selected((string) old('project_id', optional($contract)->project_id ?? '') === (string) $project->id)>{{ $project->project_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select" required>
            <option value="">Select branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', optional($contract)->branch_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Payment Method</label>
        <select name="payment_method_id" class="form-select">
            <option value="">Select payment method</option>
            @foreach($paymentMethods as $paymentMethod)
                <option value="{{ $paymentMethod->id }}" @selected((string) old('payment_method_id', optional($contract)->payment_method_id ?? '') === (string) $paymentMethod->id)>{{ $paymentMethod->method_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Payment Frequency</label>
        <select name="payment_frequency" class="form-select" required>
            <option value="">Select frequency</option>
            @foreach(\App\Models\Contract::PAYMENT_FREQUENCIES as $frequency)
                <option value="{{ $frequency }}" @selected((string) old('payment_frequency', optional($contract)->payment_frequency ?? '') === $frequency)>{{ $frequency }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Signing Date <span class="text-danger">*</span></label>
        <input type="date" name="signing_date" class="form-control" value="{{ old('signing_date', optional(optional($contract)->signing_date)->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Duration (months) <span class="text-danger">*</span></label>
        <input type="number" min="1" step="1" name="duration" class="form-control" value="{{ old('duration', optional($contract)->duration ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional(optional($contract)->start_date)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional(optional($contract)->end_date)->format('Y-m-d')) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Contract Amount</label>
        <input type="number" step="0.01" name="contract_amount" class="form-control" value="{{ old('contract_amount', optional($contract)->contract_amount ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Total Amount</label>
        <input type="number" step="0.01" name="total_amount" class="form-control" value="{{ old('total_amount', optional($contract)->total_amount ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Template Path</label>
        <input type="text" name="template_path" class="form-control" value="{{ old('template_path', optional($contract)->template_path ?? '') }}">
    </div>

    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', optional($contract)->notes ?? '') }}</textarea>
    </div>
</div>

@include('contracts._items')
