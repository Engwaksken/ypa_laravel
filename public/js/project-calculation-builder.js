(function (root) {
    'use strict';
    function schemaFromRows(inputs, formulas) {
        return {
            inputs: inputs.map(row => ({key: row.key.trim(), label: row.label.trim(), type: row.type,
                required: !!row.required, default: row.default === '' ? null : row.default,
                min: row.min === '' ? null : Number(row.min), max: row.max === '' ? null : Number(row.max)})),
            formulas: formulas.map(row => ({key: row.key.trim(), label: row.label.trim(), expression: row.expression.trim(), target: row.target || null}))
        };
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = {schemaFromRows};
    if (!root.document) return;
    const document = root.document;
    const builder = document.getElementById('projectCalculationBuilder');
    if (!builder) return;
    const inputs = builder.querySelector('[data-calculation-inputs]');
    const formulas = builder.querySelector('[data-calculation-formulas]');
    const hidden = document.getElementById('calculation_schema');
    let serial = 0;

    function control(row, key, label, value, options, required = false) {
        const wrapper = document.createElement('div');
        wrapper.className = 'col-md-4';
        const field = document.createElement(options ? 'select' : 'input');
        field.id = 'project-calc-' + (++serial);
        field.dataset.key = key;
        field.className = options ? 'form-select form-select-sm' : 'form-control form-control-sm';
        if (options) options.forEach(([v, text]) => {
            const option = document.createElement('option'); option.value = v; option.textContent = text; field.append(option);
        });
        else field.type = key === 'required' ? 'checkbox' : (key === 'min' || key === 'max' ? 'number' : 'text');
        if (field.type === 'checkbox') { field.checked = !!value; field.className = 'form-check-input'; }
        else field.value = value ?? '';
        if (field.type === 'number') field.step = 'any';
        field.required = required;
        if (key === 'key') { field.pattern = '[a-z][a-z0-9_]{0,39}'; field.maxLength = 40; field.placeholder = 'e.g. quantity'; }
        if (key === 'expression') field.maxLength = 500;
        if (key === 'label') field.maxLength = 100;
        const heading = document.createElement('label'); heading.htmlFor = field.id; heading.className = 'form-label small'; heading.textContent = label;
        wrapper.append(heading, document.createElement('br'), field); row.append(wrapper);
    }
    function add(kind, data = {}) {
        const row = document.createElement('div'); row.className = 'row g-2 border rounded p-2 mb-2';
        control(row, 'key', 'Variable key', data.key, null, true);
        control(row, 'label', 'Display label', data.label, null, true);
        if (kind === 'input') {
            control(row, 'type', 'Field type', data.type || 'number', [['number', 'Number'], ['text', 'Text']]);
            control(row, 'default', 'Default value (optional)', data.default);
            control(row, 'min', 'Minimum (numbers)', data.min);
            control(row, 'max', 'Maximum (numbers)', data.max);
            control(row, 'required', 'Required field', data.required ?? true);
        } else {
            control(row, 'expression', 'Formula', data.expression, null, true);
            control(row, 'target', 'Use result for', data.target || '', [['', 'Display only'], ['total_payable', 'Contract total payable'],
                ['projected_harvest_amount', 'Projected harvest amount'], ['monthly_payout_amount', 'Monthly payout'], ['expected_quantity', 'Expected harvest quantity']]);
        }
        const actions = document.createElement('div'); actions.className = 'col-12';
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-sm btn-outline-danger';
        remove.textContent = 'Remove ' + kind; remove.addEventListener('click', () => { row.remove(); sync(); });
        actions.append(remove); row.append(actions);
        (kind === 'input' ? inputs : formulas).append(row); sync();
    }
    function read(container) {
        return Array.from(container.children, row => Object.fromEntries(Array.from(row.querySelectorAll('[data-key]'),
            field => [field.dataset.key, field.type === 'checkbox' ? field.checked : field.value])));
    }
    function sync() { hidden.value = JSON.stringify(schemaFromRows(read(inputs), read(formulas))); }
    root.YpaProjectCalculationBuilder = {
        load(schema) {
            inputs.replaceChildren(); formulas.replaceChildren();
            (schema?.inputs || []).forEach(row => add('input', row));
            (schema?.formulas || []).forEach(row => add('formula', row)); sync();
        }, sync
    };
    builder.addEventListener('input', sync); builder.addEventListener('change', sync);
    builder.querySelector('[data-add-project-input]').addEventListener('click', () => { if (inputs.children.length < 30) add('input'); });
    builder.querySelector('[data-add-project-formula]').addEventListener('click', () => { if (formulas.children.length < 30) add('formula'); });
})(typeof window !== 'undefined' ? window : globalThis);
