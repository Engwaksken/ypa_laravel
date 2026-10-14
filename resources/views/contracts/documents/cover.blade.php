<section class="cover-page">
    <h2>THE REPUBLIC OF UGANDA</h2>
    <p>IN THE MATTER OF THE</p>
    <h2>CONTRACTS ACT, CAP 284</h2>
    @if($document['logo'])<img class="cover-logo" src="{{ $document['logo'] }}" alt="YPA Logo">@endif
    <h1>{{ strtoupper((string) ($contract->project?->project_name ?? 'Contract Project')) }}</h1>
    @if($document['mode'])<h2 class="cover-mode">{{ $document['mode'] }}</h2>@endif
    <p class="cover-contract">Contract No: {{ $contract->contract_number }}</p>
    <div class="cover-party"><h2>{{ $document['partyName'] ?: '-' }}</h2><p>{{ $document['partyCode'] ?: '-' }}</p></div>
</section>
