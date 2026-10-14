@php
    $partyName = strtolower((string) $contract->contract_for) === 'group' ? $contract->group?->group_name : $contract->member?->full_name;
@endphp
<section class="manual-signing-block">
    <p><strong>IN WITNESS WHEREOF the parties have appended their signatures as hereunder;</strong></p>
    <h3>Signed by the client</h3>
    <table class="sig-doc-table"><tr><td>Name:</td><td>{{ $partyName ?: '-' }}</td></tr><tr><td>Signature:</td><td><span class="sig-line"></span></td></tr><tr><td>Date:</td><td><span class="sig-line"></span></td></tr></table>
    <h3>Witnessed by</h3>
    <table class="sig-doc-table"><tr><td>Name:</td><td><span class="sig-line"></span></td></tr><tr><td>Signature:</td><td><span class="sig-line"></span></td></tr><tr><td>Date:</td><td><span class="sig-line"></span></td></tr></table>
    @include('contracts.documents.organization-signatures')
</section>
