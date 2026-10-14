<h2 class="project-title">{{ $contract->project?->project_name ?? 'Contract Agreement' }}</h2>
<table class="party-photos">
    <tr><th>{{ $document['partyLabel'] }}</th><th>NEXT OF KIN</th></tr>
    <tr>
        <td>@if($document['partyPhoto'])<img src="{{ $document['partyPhoto'] }}" alt="{{ $document['partyLabel'] }}">@else<span class="missing-photo">Photo not recorded</span>@endif</td>
        <td>@if($document['kinPhoto'])<img src="{{ $document['kinPhoto'] }}" alt="Next of kin">@else<span class="missing-photo">Photo not recorded</span>@endif</td>
    </tr>
</table>
