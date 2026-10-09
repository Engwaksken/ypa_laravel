{{-- Add/Edit receivable modal. Requires $branches, $members and $groups. --}}
@php($receivableCategories = ['Farm and Livestock Related', 'Services', 'Administrative / Other'])
<div class="modal fade" id="receivableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="receivableForm" method="POST" action="{{ route('receivables.store') }}">
                @csrf
                <input type="hidden" name="_method" id="receivableMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="receivableModalTitle">Add Receivable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-section-title">Payer</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Received Date</label>
                            <input type="date" name="received_date" id="receivableDate" value="{{ now()->format('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payer Type</label>
                            <select name="payer_type" id="receivablePayerType" class="form-select" required>
                                <option value="Member">Member</option>
                                <option value="Non-Member">Non-Member</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="receivableBranch" class="form-select">
                                <option value="">Select branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Member</label>
                            <select name="member_id" id="receivableMember" class="form-select">
                                <option value="">No member</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Group</label>
                            <select name="group_id" id="receivableGroup" class="form-select">
                                <option value="">No group</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->group_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payer Name</label>
                            <input type="text" name="payer_name" id="receivablePayerName" class="form-control" maxlength="150" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payer Phone</label>
                            <input type="text" name="payer_phone" id="receivablePayerPhone" class="form-control" maxlength="30">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Receiver Email</label>
                            <input type="email" name="receiver_email" id="receivableEmail" class="form-control" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Group Name</label>
                            <input type="text" name="group_name" id="receivableGroupName" class="form-control" maxlength="180">
                        </div>
                    </div>

                    <div class="modal-section-title mt-4">Receivable</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category" id="receivableCategory" class="form-select" required>
                                @foreach($receivableCategories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Receivable Type</label>
                            <input type="text" name="receivable_type" id="receivableType" class="form-control" maxlength="150" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Other Type</label>
                            <input type="text" name="other_type" id="receivableOtherType" class="form-control" maxlength="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount Payable</label>
                            <input type="number" step="0.01" min="0" name="amount_payable" id="receivableAmountPayable" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discount</label>
                            <input type="number" step="0.01" min="0" name="discount" id="receivableDiscount" value="0" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="receivableDescription" rows="3" class="form-control"></textarea>
                        </div>
                    </div>

                    {{-- Initial payment: only on create. Later payments use the Record Payment flow. --}}
                    <div id="receivablePaymentSection">
                        <div class="modal-section-title mt-4">Initial Payment</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Amount Paid</label>
                                <input type="number" step="0.01" min="0" name="amount_paid" id="receivableAmountPaid" value="0" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" id="receivablePaymentMethod" class="form-select" required>
                                    @foreach(['Cash', 'Mobile Money', 'Bank'] as $method)
                                        <option value="{{ $method }}">{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payment Reference</label>
                                <input type="text" name="payment_reference" id="receivablePaymentReference" class="form-control" maxlength="150">
                            </div>
                        </div>
                        <div class="form-text mt-2">Status is derived automatically from the amount paid versus the net payable.</div>
                    </div>
                    <div id="receivableEditNote" class="alert alert-info small mt-4 mb-0 d-none">
                        <i class="fas fa-info-circle me-1"></i> Amount paid, status and payment details are managed through the Record Payment flow on the receivable's page.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function receivableModalMode(isEdit) {
        // In edit mode the payment section is hidden. payment_method stays
        // submitted (the request requires it; update() ignores it), the other
        // payment inputs are disabled so they are not sent.
        document.getElementById('receivablePaymentSection').classList.toggle('d-none', isEdit);
        document.getElementById('receivableEditNote').classList.toggle('d-none', !isEdit);
        document.getElementById('receivableAmountPaid').disabled = isEdit;
        document.getElementById('receivablePaymentReference').disabled = isEdit;
    }

    function receivableModalShow() {
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('receivableModal')).show();
        }
    }

    function openReceivableModal() {
        var form = document.getElementById('receivableForm');
        form.reset();
        form.action = "{{ route('receivables.store') }}";
        document.getElementById('receivableMethod').value = 'POST';
        document.getElementById('receivableModalTitle').textContent = 'Add Receivable';
        receivableModalMode(false);
        receivableModalShow();
    }

    function openReceivableEdit(btn) {
        var form = document.getElementById('receivableForm');
        var d = btn.dataset;
        form.reset();
        form.action = d.url;
        document.getElementById('receivableMethod').value = 'PUT';
        document.getElementById('receivableModalTitle').textContent = 'Edit Receivable';
        var map = {
            receivableDate: d.date, receivablePayerType: d.payerType, receivableBranch: d.branch,
            receivableMember: d.member, receivableGroup: d.group, receivablePayerName: d.payerName,
            receivablePayerPhone: d.payerPhone, receivableEmail: d.email, receivableGroupName: d.groupName,
            receivableCategory: d.category, receivableType: d.type, receivableOtherType: d.otherType,
            receivableAmountPayable: d.amountPayable, receivableDiscount: d.discount,
            receivableDescription: d.description, receivablePaymentMethod: d.paymentMethod || 'Cash'
        };
        Object.keys(map).forEach(function (id) {
            document.getElementById(id).value = map[id] || '';
        });
        receivableModalMode(true);
        receivableModalShow();
    }

    // When the validation helper reopens the modal after a failed update,
    // restore edit mode.
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('receivableMethod').value === 'PUT') {
            receivableModalMode(true);
        }
    });
</script>
@endpush
