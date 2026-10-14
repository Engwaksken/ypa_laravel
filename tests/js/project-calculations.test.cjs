const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const {schemaFromRows} = require('../../public/js/project-calculation-builder.js');
const {schemaFor, previewPayload} = require('../../public/js/contract-project-calculation.js');

test('builder serializes user-defined fields, constraints and mapped formulas', () => {
    const schema = schemaFromRows([{key: ' ponds ', label: ' Ponds ', type: 'number', required: true, default: '2', min: '1', max: ''}],
        [{key: ' cost ', label: ' Cost ', expression: ' ponds * 500 ', target: 'total_payable'}]);
    assert.deepEqual(schema.inputs[0], {key: 'ponds', label: 'Ponds', type: 'number', required: true, default: '2', min: 1, max: null});
    assert.equal(schema.formulas[0].expression, 'ponds * 500');
    assert.equal(schema.formulas[0].target, 'total_payable');
});

test('existing contracts use saved schemas; legacy agreements remain manual', () => {
    const schema = {inputs: [], formulas: []};
    assert.equal(schemaFor({savedProjectId: 4, snapshot: {schema}, projects: {4: {new: true}}}, '4'), schema);
    assert.equal(schemaFor({savedProjectId: 4, snapshot: null, projects: {4: {new: true}}}, '4'), null);
});

test('preview posts only defined inputs and identifying context, never user-calculated totals', () => {
    assert.deepEqual(previewPayload('5', null, '12', [{key: 'ponds', value: '4'}]),
        {project_id: '5', contract_id: null, duration: '12', project_inputs: {ponds: '4'}});
});

class Element {
    constructor(tag = 'div') { this.tagName = tag.toUpperCase(); this.children = []; this.listeners = {}; this.value = ''; this.dataset = {}; this.textContent = ''; }
    append(...nodes) { nodes.forEach(node => { node.parent = this; }); this.children.push(...nodes); }
    replaceChildren(...nodes) { this.children = nodes; }
    addEventListener(name, callback) { this.listeners[name] = callback; }
    querySelectorAll(selector) { return this.children.flatMap(child => [...((selector === 'input' && child.tagName === 'INPUT') || (selector === '[data-key]' && child.dataset.key) ? [child] : []), ...child.querySelectorAll(selector)]); }
    remove() { this.parent.children = this.parent.children.filter(child => child !== this); }
    checkValidity() { return true; }
    reportValidity() {}
}
function environment() {
    const project = new Element('select'); project.value = '4';
    const duration = new Element('input'); duration.value = '12';
    const amount = new Element('input'); const total = new Element('input');
    const panel = new Element(); const inputs = new Element(); const formulas = new Element(); const results = new Element(); const status = new Element(); const button = new Element('button'); const description = new Element();
    panel.querySelector = selector => ({'[data-project-input-fields]': inputs, '[data-project-formula-list]': formulas, '[data-project-calculation-results]': results,
        '[data-project-calculation-status]': status, '[data-project-calculate]': button, '[data-project-calculation-description]': description})[selector];
    const form = {querySelector: selector => ({'[name="project_id"]': project, '[name="duration"]': duration, '[name="contract_amount"]': amount, '[name="total_amount"]': total})[selector]};
    panel.closest = () => form;
    const schema = {inputs: [{key: 'ponds', label: '<script>ponds</script>', type: 'number', required: true, min: 1, default: 2}],
        formulas: [{key: 'cost', label: 'Cost', expression: 'ponds * 500', target: 'total_payable'}]};
    const config = {projects: {4: schema, 5: {inputs: [], formulas: []}}, initialProjectId: 4, values: {ponds: 4}, url: '/calculate', snapshot: null, contractId: null};
    const calls = [];
    const context = {document: {getElementById: id => id === 'contractProjectCalculation' ? panel : {textContent: JSON.stringify(config)},
        createElement: tag => new Element(tag), querySelector: () => ({content: 'csrf-token'})}, AbortController,
        fetch: (url, options) => new Promise(resolve => calls.push({url, options, resolve}))};
    context.window = context;
    vm.runInNewContext(fs.readFileSync(require.resolve('../../public/js/contract-project-calculation.js'), 'utf8'), context);
    return {project, duration, panel, inputs, results, status, button, amount, total, calls};
}
const flush = () => new Promise(resolve => setImmediate(resolve));

test('contract fields come from selected project and preview fills mapped totals', async () => {
    const env = environment();
    assert.equal(env.panel.hidden, false);
    assert.equal(env.inputs.children[0].children[0].textContent, '<script>ponds</script> *');
    assert.equal(env.inputs.querySelectorAll('input')[0].value, 4);
    assert.equal(env.amount.readOnly, true);
    const pending = env.button.listeners.click();
    assert.equal(env.calls[0].options.headers['X-CSRF-TOKEN'], 'csrf-token');
    assert.deepEqual(JSON.parse(env.calls[0].options.body).project_inputs, {ponds: 4});
    env.calls[0].resolve({ok: true, json: async () => ({calculation: {results: {cost: 2000}, targets: {total_payable: 2000}}})});
    await pending;
    assert.equal(env.total.value, 2000);
    assert.equal(env.results.children[0].textContent, 'Cost: 2000');
    assert.equal(env.button.disabled, false);
});

test('project changes abort pending previews and cannot display stale results', async () => {
    const env = environment();
    const pending = env.button.listeners.click();
    env.project.value = '5'; env.project.listeners.change();
    assert.equal(env.calls[0].options.signal.aborted, true);
    assert.equal(env.panel.hidden, true);
    assert.equal(env.amount.readOnly, false);
    env.calls[0].resolve({ok: true, json: async () => ({calculation: {results: {cost: 2000}, targets: {total_payable: 2000}}})});
    await pending;
    assert.equal(env.total.value, '');
    assert.equal(env.results.children.length, 0);
});

test('validation errors leave the form usable and do not change totals', async () => {
    const env = environment(); const pending = env.button.listeners.click();
    env.calls[0].resolve({ok: false, json: async () => ({errors: {project_inputs: ['Division by zero.']}})});
    await pending; await flush();
    assert.equal(env.status.textContent, 'Division by zero.');
    assert.equal(env.button.disabled, false);
    assert.equal(env.total.value, '');
});

test('builder add, edit, remove and reset keep the submitted definition in sync', () => {
    const builder = new Element(); const inputs = new Element(); const formulas = new Element(); const hidden = new Element('input');
    const addInput = new Element('button'); const addFormula = new Element('button');
    builder.querySelector = selector => ({'[data-calculation-inputs]': inputs, '[data-calculation-formulas]': formulas,
        '[data-add-project-input]': addInput, '[data-add-project-formula]': addFormula})[selector];
    const context = {document: {getElementById: id => id === 'projectCalculationBuilder' ? builder : hidden, createElement: tag => new Element(tag)}};
    context.window = context;
    vm.runInNewContext(fs.readFileSync(require.resolve('../../public/js/project-calculation-builder.js'), 'utf8'), context);
    addInput.listeners.click();
    assert.equal(inputs.children.length, 1);
    const fields = inputs.querySelectorAll('[data-key]');
    fields.find(field => field.dataset.key === 'key').value = 'quantity';
    fields.find(field => field.dataset.key === 'label').value = 'Quantity';
    builder.listeners.input();
    assert.equal(JSON.parse(hidden.value).inputs[0].key, 'quantity');
    context.YpaProjectCalculationBuilder.load({inputs: [{key: 'ponds', label: '<b>Ponds</b>', type: 'number', required: false, min: 1}],
        formulas: [{key: 'cost', label: 'Cost', expression: 'ponds * 500', target: 'total_payable'}]});
    assert.equal(JSON.parse(hidden.value).inputs[0].required, false);
    assert.equal(JSON.parse(hidden.value).formulas[0].expression, 'ponds * 500');
    const remove = formulas.children[0].children.at(-1).children[0];
    remove.listeners.click();
    assert.equal(JSON.parse(hidden.value).formulas.length, 0);
    context.YpaProjectCalculationBuilder.load(null);
    assert.deepEqual(JSON.parse(hidden.value), {inputs: [], formulas: []});
});
