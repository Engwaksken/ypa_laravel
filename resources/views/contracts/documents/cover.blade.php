@php
    $isGroup = strtolower((string) $contract->contract_for) === 'group';
    $partyName = $isGroup ? $contract->group?->group_name : $contract->member?->full_name;
    $partyCode = $isGroup ? $contract->group?->group_code : $contract->member?->membership_id;
@endphp
<section class="cover-page">
    <h2>THE REPUBLIC OF UGANDA</h2>
    <p>IN THE MATTER OF THE</p>
    <h2>CONTRACTS ACT, CAP 284</h2>
    @if($siteLogo && is_file(public_path($siteLogo)))<p><img src="{{ public_path($siteLogo) }}" alt="YPA Logo" style="max-height:80px;max-width:170px;"></p>@endif
    <h1>{{ strtoupper((string) ($contract->project?->project_name ?? 'Contract Project')) }}</h1>
    <h2>{{ $template->template_sections['title'] ?? $template->template_name }}</h2>
    <p><strong>Contract No: {{ $contract->contract_number }}</strong></p>
    <div class="cover-party"><h2>{{ $partyName ?: '-' }}</h2><p>{{ $partyCode ?: '-' }}</p><p>{{ $siteName }}</p></div>
</section>
