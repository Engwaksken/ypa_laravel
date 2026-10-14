@if($contract->project_calculation)
<section class="section border rounded p-3 my-3" data-table-tools="off">
    <h3>Project Details and Calculations</h3>
    <table class="details table table-sm">
        @foreach($contract->project_calculation['schema']['inputs'] as $field)
            <tr><td>{{ $field['label'] }}</td><td>{{ $contract->project_calculation['inputs'][$field['key']] ?? '-' }}</td></tr>
        @endforeach
        @foreach($contract->project_calculation['schema']['formulas'] as $formula)
            <tr><td>{{ $formula['label'] }}</td><td>{{ $contract->project_calculation['results'][$formula['key']] ?? '-' }}</td></tr>
        @endforeach
    </table>
    <p class="small muted text-muted">Calculated using the project definition saved with this contract.</p>
</section>
@endif
