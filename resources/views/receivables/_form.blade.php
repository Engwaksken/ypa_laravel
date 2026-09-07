@csrf
@if(isset($receivable))
    @method('PUT')
@endif

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Received Date</label>
        <input type="date" name="received_date" value="{{ old('received_date', optional(optional($receivable ?? null)->received_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Payer Type</label>
        <select name="payer_type" class="form-select" required>
            @foreach(['Member','Non-Member'] as $type)
                <option value="{{ $type }}" @selected(old('payer_type', optional($receivable ?? null)->payer_type ?? 'Member') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select">
            <option value="">Select branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', optional($receivable ?? null)->branch_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Member</label>
        <select name="member_id" class="form-select">
            <option value="">No member</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" @selected((string) old('member_id', optional($receivable ?? null)->member_id ?? '') === (string) $member->id)>{{ $member->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select">
            <option value="">No group</option>
            @foreach($groups as $group)
                <option value="{{ $group->id }}" @selected((string) old('group_id', optional($receivable ?? null)->group_id ?? '') === (string) $group->id)>{{ $group->group_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Payer Name</label>
        <input type="text" name="payer_name" value="{{ old('payer_name', optional($receivable ?? null)->payer_name) }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Payer Phone</label>
        <input type="text" name="payer_phone" value="{{ old('payer_phone', optional($receivable ?? null)->payer_phone) }}" class="form-control">
    </div>
    <div class="col-md-3">
        <label class="form-label">Receiver Email</label>
        <input type="email" name="receiver_email" value="{{ old('receiver_email', optional($receivable ?? null)->receiver_email) }}" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">Category</label>
        <select name="category" class="form-select" required>
            @foreach(['Farm and Livestock Related','Services','Administrative / Other'] as $category)
                <option value="{{ $category }}" @selected(old('category', optional($receivable ?? null)->category) === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Receivable Type</label>
        <input type="text" name="receivable_type" value="{{ old('receivable_type', optional($receivable ?? null)->receivable_type) }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Other Type</label>
        <input type="text" name="other_type" value="{{ old('other_type', optional($receivable ?? null)->other_type) }}" class="form-control">
    </div>

    <div class="col-md-3">
        <label class="form-label">Amount Payable</label>
        <input type="number" step="0.01" min="0" name="amount_payable" value="{{ old('amount_payable', optional($receivable ?? null)->amount_payable) }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Discount</label>
        <input type="number" step="0.01" min="0" name="discount" value="{{ old('discount', optional($receivable ?? null)->discount ?? 0) }}" class="form-control">
    </div>
    @if(isset($receivable))
        <div class="col-md-3">
            <label class="form-label">Amount Paid</label>
            <input type="number" step="0.01" min="0" value="{{ old('amount_paid', optional($receivable)->amount_paid ?? 0) }}" class="form-control" disabled>
            <small class="text-muted">Payments are recorded via the Record Payment flow.</small>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <input type="text" value="{{ optional($receivable)->status ?? '-' }}" class="form-control" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label">Payment Method</label>
            <input type="text" value="{{ optional($receivable)->payment_method ?? '-' }}" class="form-control" disabled>
            <input type="hidden" name="payment_method" value="{{ optional($receivable)->payment_method ?? 'Cash' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Payment Reference</label>
            <input type="text" value="{{ optional($receivable)->payment_reference ?? '-' }}" class="form-control" disabled>
        </div>
    @else
        <div class="col-md-3">
            <label class="form-label">Amount Paid</label>
            <input type="number" step="0.01" min="0" name="amount_paid" value="{{ old('amount_paid', 0) }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach(['Received','Pending','Cancelled'] as $status)
                    <option value="{{ $status }}" @selected(old('status', 'Pending') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
                @foreach(['Cash','Mobile Money','Bank'] as $method)
                    <option value="{{ $method }}" @selected(old('payment_method', 'Cash') === $method)>{{ $method }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Payment Reference</label>
            <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" class="form-control">
        </div>
    @endif
    <div class="col-md-4">
        <label class="form-label">Group Name</label>
        <input type="text" name="group_name" value="{{ old('group_name', optional($receivable ?? null)->group_name) }}" class="form-control">
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" rows="4" class="form-control">{{ old('description', optional($receivable ?? null)->description) }}</textarea>
    </div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Save</button>
        <a href="{{ route('receivables.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>
