@php
    $projectCalculationConfig = [
        'projects' => $projects->mapWithKeys(fn ($project) => [$project->id => $project->calculation_schema])->all(),
        'savedProjectId' => $contract?->project_id,
        'snapshot' => $contract?->project_calculation,
        'initialProjectId' => old('project_id', $contract?->project_id),
        'values' => old('project_inputs', $contract?->project_calculation['inputs'] ?? []),
        'contractId' => $contract?->id,
        'url' => route('contracts.project-calculation'),
    ];
@endphp
<section class="border rounded p-3 my-3" id="contractProjectCalculation" hidden>
    <h6>Project-specific details and calculations</h6>
    <p class="small text-muted" data-project-calculation-description></p>
    <div class="row g-3" data-project-input-fields></div>
    <div class="mt-3" data-project-formula-list></div>
    <button type="button" class="btn btn-outline-primary mt-2" data-project-calculate>Calculate / Preview</button>
    <p class="small mt-2 mb-0" role="status" aria-live="polite" data-project-calculation-status></p>
    <div class="mt-2" data-project-calculation-results></div>
</section>
@push('scripts')
<script type="application/json" id="projectCalculationConfig">@json($projectCalculationConfig)</script>
<script src="{{ asset('js/contract-project-calculation.js') }}?v={{ filemtime(public_path('js/contract-project-calculation.js')) }}"></script>
@endpush
