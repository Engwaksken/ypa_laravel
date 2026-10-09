{{-- Edit button that opens the receivable modal pre-filled. Needs $receivable, $buttonClass, $label. --}}
<button type="button" class="{{ $buttonClass }}" title="Edit"
    data-url="{{ route('receivables.update', $receivable) }}"
    data-date="{{ optional($receivable->received_date)->format('Y-m-d') }}"
    data-payer-type="{{ $receivable->payer_type }}"
    data-branch="{{ $receivable->branch_id ?? '' }}"
    data-member="{{ $receivable->member_id ?? '' }}"
    data-group="{{ $receivable->group_id ?? '' }}"
    data-payer-name="{{ $receivable->payer_name }}"
    data-payer-phone="{{ $receivable->payer_phone ?? '' }}"
    data-email="{{ $receivable->receiver_email ?? '' }}"
    data-group-name="{{ $receivable->group_name ?? '' }}"
    data-category="{{ $receivable->category }}"
    data-type="{{ $receivable->receivable_type }}"
    data-other-type="{{ $receivable->other_type ?? '' }}"
    data-amount-payable="{{ $receivable->amount_payable }}"
    data-discount="{{ $receivable->discount ?? 0 }}"
    data-description="{{ $receivable->description ?? '' }}"
    data-payment-method="{{ $receivable->payment_method ?? 'Cash' }}"
    onclick="openReceivableEdit(this)">
    <i class="fas fa-edit{{ $label !== '' ? ' me-1' : '' }}"></i>{{ $label }}
</button>
