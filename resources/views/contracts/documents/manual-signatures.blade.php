@php
    $partyName = strtolower((string) $contract->contract_for) === 'group' ? $contract->group?->group_name : $contract->member?->full_name;
@endphp
<section class="manual-signing-block">
    <p><strong>Date:</strong> <span class="sig-line"></span></p>
    <p><strong>IN WITNESS WHEREOF the parties have appended their signatures as hereunder;</strong></p>
    <h3>SIGNED BY THE SAID {{ strtolower((string) $contract->contract_for) === 'group' ? 'GROUP REPRESENTATIVE' : 'FARMER' }};</h3>
    <table class="sig-doc-table"><tr><td class="sig-label">Name:</td><td>{{ $partyName ?: '-' }}</td></tr><tr><td class="sig-label">Signature:</td><td><span class="sig-line"></span></td></tr><tr><td class="sig-label">Date:</td><td><span class="sig-line date"></span></td></tr></table>
    <h3>Witnessed by</h3>
    <table class="sig-doc-table"><tr><td class="sig-label">Name:</td><td><span class="sig-line"></span></td></tr><tr><td class="sig-label">Signature:</td><td><span class="sig-line"></span></td></tr><tr><td class="sig-label">Date:</td><td><span class="sig-line date"></span></td></tr></table>
    <h3>Signed for and on behalf of {{ $template?->template_sections['ypa_full_name'] ?? $siteName }}:</h3>
    @foreach(['Record’s Officer', 'Chief Accountant / Finance', 'Director'] as $role)
        @if($role === 'Director')<h3>Approved by;</h3>@endif
        <table class="sig-doc-table">
            <tr><td class="sig-label">{{ $role }}:</td><td>@if($role === 'Director' && $contract->director_name){{ $contract->director_name }}@else<span class="sig-line"></span>@endif</td></tr>
            <tr><td class="sig-label">Signature{{ $role === 'Director' ? ' / Stamp' : '' }}:</td><td><span class="sig-line"></span></td></tr>
            <tr><td class="sig-label">Date:</td><td><span class="sig-line date"></span></td></tr>
        </table>
    @endforeach
</section>
