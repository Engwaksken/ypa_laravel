<section class="border rounded p-3 mt-4" id="projectCalculationBuilder">
    <h6>Project fields and calculations</h6>
    <p class="small text-muted">Define the details needed for this project instead of adding code for each project type. Leave empty for manual amounts. Existing contracts retain their saved definitions.</p>
    <input type="hidden" name="calculation_schema" id="calculation_schema" value='{"inputs":[],"formulas":[]}'>
    <h6>Input fields</h6>
    <div data-calculation-inputs></div>
    <button type="button" class="btn btn-sm btn-outline-primary" data-add-project-input>Add input field</button>
    <h6 class="mt-3">Calculated values</h6>
    <p class="small text-muted">Use keys such as <code>quantity * unit_cost + registration_fee</code>. Available operators: + − * / and parentheses; functions: min, max, round. Built-in variables: registration_fee, administrative_fee, duration (months). Formulas run in order and may use earlier results, not text fields.</p>
    <div data-calculation-formulas></div>
    <button type="button" class="btn btn-sm btn-outline-primary" data-add-project-formula>Add formula</button>
    <p class="small text-muted mt-3 mb-0">“Use result for” links a formula to contract totals or projections. Other results are displayed only. Contract templates can use <code>@{{project.quantity}}</code> or any field/result key with the <code>project.</code> prefix.</p>
</section>
