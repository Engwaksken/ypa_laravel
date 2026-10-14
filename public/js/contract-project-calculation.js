(function (root) {
    'use strict';
    function schemaFor(config, projectId) {
        return String(projectId) === String(config.savedProjectId)
            ? (config.snapshot?.schema || null) : config.projects[projectId];
    }
    function previewPayload(projectId, contractId, duration, fields) {
        return {project_id: projectId, contract_id: contractId || null, duration,
            project_inputs: Object.fromEntries(fields.map(field => [field.key, field.value]))};
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = {schemaFor, previewPayload};
    if (!root.document) return;
    const document = root.document;
    const panel = document.getElementById('contractProjectCalculation');
    if (!panel) return;
    const config = JSON.parse(document.getElementById('projectCalculationConfig').textContent);
    const form = panel.closest('form');
    const project = form.querySelector('[name="project_id"]');
    const duration = form.querySelector('[name="duration"]');
    const inputContainer = panel.querySelector('[data-project-input-fields]');
    const formulaContainer = panel.querySelector('[data-project-formula-list]');
    const results = panel.querySelector('[data-project-calculation-results]');
    const status = panel.querySelector('[data-project-calculation-status]');
    const button = panel.querySelector('[data-project-calculate]');
    let revision = 0;
    let controller;
    let schema;
    function invalidate() {
        ++revision; controller?.abort(); button.disabled = false;
        results.replaceChildren(); status.textContent = 'Values are recalculated on the server when you save.';
    }
    function render(initial = false) {
        invalidate(); schema = schemaFor(config, project.value);
        inputContainer.replaceChildren(); formulaContainer.replaceChildren();
        const configured = !!schema && !!(schema.inputs.length || schema.formulas.length);
        panel.hidden = !configured;
        const totalIsCalculated = configured && schema.formulas.some(formula => formula.target === 'total_payable');
        ['contract_amount', 'total_amount'].forEach(name => { const field = form.querySelector('[name="' + name + '"]'); if (field) field.readOnly = totalIsCalculated; });
        if (!configured) return;
        panel.querySelector('[data-project-calculation-description]').textContent = config.snapshot && String(project.value) === String(config.savedProjectId)
            ? 'Using this contract’s saved formula definitions and fees. Project configuration changes do not change existing contracts.'
            : 'Fields and formulas come from the selected project. Mapped totals are calculated, not entered manually.';
        schema.inputs.forEach((definition, index) => {
            const wrapper = document.createElement('div'); wrapper.className = 'col-md-4';
            const input = document.createElement('input'); input.className = 'form-control';
            input.id = 'contract-project-input-' + index; input.name = 'project_inputs[' + definition.key + ']'; input.dataset.key = definition.key;
            input.type = definition.type === 'number' ? 'number' : 'text'; input.required = !!definition.required;
            if (input.type === 'number') {
                input.step = 'any'; if (definition.min != null) input.min = definition.min; if (definition.max != null) input.max = definition.max;
            } else input.maxLength = 500;
            const values = initial && String(project.value) === String(config.initialProjectId) ? config.values :
                (String(project.value) === String(config.savedProjectId) ? (config.snapshot?.inputs || {}) : {});
            input.value = (Object.hasOwn(values, definition.key) ? values[definition.key] : null) ?? definition.default ?? '';
            const label = document.createElement('label'); label.htmlFor = input.id; label.className = 'form-label'; label.textContent = definition.label + (definition.required ? ' *' : '');
            wrapper.append(label, input); inputContainer.append(wrapper);
        });
        schema.formulas.forEach(definition => {
            const line = document.createElement('p'); line.className = 'small mb-1';
            line.textContent = definition.label + ' = ' + definition.expression + (definition.target ? ' → ' + definition.target : ''); formulaContainer.append(line);
        });
    }
    project.addEventListener('change', () => render());
    inputContainer.addEventListener('input', invalidate); duration.addEventListener('input', invalidate);
    button.addEventListener('click', async () => {
        if (!duration.checkValidity()) { duration.reportValidity(); return; }
        const fields = Array.from(inputContainer.querySelectorAll('input'));
        if (fields.some(field => { if (field.checkValidity()) return false; field.reportValidity(); return true; })) return;
        invalidate(); const current = revision; controller = new AbortController(); button.disabled = true; status.textContent = 'Calculating…';
        try {
            const response = await root.fetch(config.url, {method: 'POST', signal: controller.signal, credentials: 'same-origin', cache: 'no-store',
                headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify(previewPayload(project.value, config.contractId, duration.value, fields.map(field => ({key: field.dataset.key, value: field.value}))))});
            const data = await response.json();
            if (current !== revision) return;
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Unable to calculate.'));
            if (!data.calculation) { status.textContent = 'No calculations configured for this project.'; return; }
            schema.formulas.forEach(definition => {
                const line = document.createElement('p'); line.className = 'fw-semibold mb-1'; line.textContent = definition.label + ': ' + data.calculation.results[definition.key]; results.append(line);
            });
            const total = data.calculation.targets.total_payable;
            if (total != null) ['contract_amount', 'total_amount'].forEach(name => { form.querySelector('[name="' + name + '"]').value = total; });
            status.textContent = 'Preview calculated. Results are recalculated and saved on the server when you save the contract.';
        } catch (error) {
            if (current === revision && error.name !== 'AbortError') status.textContent = error.message || 'Unable to calculate.';
        } finally { if (current === revision) button.disabled = false; }
    });
    render(true);
})(typeof window !== 'undefined' ? window : globalThis);
