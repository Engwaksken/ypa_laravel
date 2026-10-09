const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const code = fs.readFileSync(path.resolve(__dirname, '../../public/js/pos.js'), 'utf8');
function setup() {
    const elements = new Map();
    const listeners = {};
    const row = {hidden: false, dataset: {search: 'beans b-1'}};
    const input = {value: '0', dataset: {id: '7', price: '1250'}, checkValidity: () => true, closest: () => row};
    const element = id => {
        if (!elements.has(id)) elements.set(id, {value: '', hidden: false, textContent: '', disabled: false, addEventListener(name, fn) {listeners[id + ':' + name] = fn;}});
        return elements.get(id);
    };
    const form = element('posForm');
    form.querySelectorAll = selector => selector === '.pos-quantity' ? [input] : [row];
    form.checkValidity = () => true;
    form.reportValidity = () => { form.reported = true; };
    element('posDiscount').value = '0';
    element('posPaymentStatus').value = 'paid';
    vm.runInNewContext(code, {document: {getElementById: element}, window: {addEventListener(name, fn) {listeners[name] = fn;}}});
    function submit() {
        const event = {prevented: false, preventDefault() {this.prevented = true;}};
        listeners['posForm:submit'](event);
        return event;
    }
    return {element, input, row, form, listeners, submit};
}
test('POS blocks empty cart without locking the invoice', () => {
    const env = setup();
    assert.equal(env.submit().prevented, true);
    assert.equal(env.element('posError').textContent, 'Select at least one product.');
    assert.equal(env.element('posSubmit').disabled, false);
});
test('POS serializes only product IDs and quantities and blocks a repeat submit', () => {
    const env = setup();
    env.input.value = '2';
    assert.equal(env.submit().prevented, false);
    assert.deepEqual(JSON.parse(env.element('posItems').value), [{id: 7, quantity: 2}]);
    assert.equal(env.element('posTotal').textContent, 'UGX 2,500.00');
    assert.equal(env.submit().prevented, true);
    env.listeners.pageshow();
    assert.equal(env.element('posSubmit').disabled, false);
});
test('POS partial payments require a payer and cap amount at current discounted total', () => {
    const env = setup();
    env.input.value = '2';
    env.element('posPaymentStatus').value = 'partially paid';
    env.element('posDiscount').value = '500';
    env.element('posAmountPaid').value = '600';
    env.listeners['posForm:input']();
    assert.equal(env.element('posAmountPaid').required, true);
    assert.equal(env.element('posAmountPaid').disabled, false);
    assert.equal(env.element('posAmountPaid').max, '2000.00');
    assert.equal(env.element('posCustomer').required, true);
    assert.equal(env.element('posBalance').textContent, 'UGX 1,400.00');
    env.element('posPaymentStatus').value = 'oncredit';
    env.listeners['posForm:change']();
    assert.equal(env.element('posAmountPaid').disabled, true);
    assert.equal(env.element('posBalance').textContent, 'UGX 2,000.00');
});
test('POS unhides invalid product quantity before reporting native validation', () => {
    const env = setup();
    env.input.value = '999';
    env.input.checkValidity = () => false;
    env.form.checkValidity = () => false;
    env.row.hidden = true;
    assert.equal(env.submit().prevented, true);
    assert.equal(env.row.hidden, false);
    assert.equal(env.form.reported, true);
    assert.equal(env.element('posSubmit').disabled, false);
});
test('POS search filters rows without discarding selected quantities', () => {
    const env = setup();
    env.input.value = '3';
    env.listeners['posSearch:input']({target: {value: 'maize'}});
    assert.equal(env.row.hidden, true);
    env.listeners['posSearch:input']({target: {value: 'B-1'}});
    assert.equal(env.row.hidden, false);
    assert.equal(env.input.value, '3');
});
test('POS member and group payer tokens remain selected through payment changes', () => {
    for (const payer of ['member_12', 'group_34']) {
        const env = setup();
        env.element('posCustomer').value = payer;
        env.element('posPaymentStatus').value = 'oncredit';
        env.listeners['posForm:change']();
        assert.equal(env.element('posCustomer').value, payer);
        assert.equal(env.element('posCustomer').required, true);
        env.element('posPaymentStatus').value = 'paid';
        env.listeners['posForm:change']();
        assert.equal(env.element('posCustomer').value, payer);
        assert.equal(env.element('posCustomer').required, false);
    }
});
test('POS permits a fully discounted paid invoice and displays no outstanding balance', () => {
    const env = setup();
    env.input.value = '2';
    env.element('posDiscount').value = '2500';
    assert.equal(env.submit().prevented, false);
    assert.equal(env.element('posTotal').textContent, 'UGX 0.00');
    assert.equal(env.element('posBalance').textContent, 'UGX 0.00');
});
test('POS recomputes totals and partial payment bounds with refreshed catalog prices', () => {
    const env = setup();
    env.input.value = '2';
    env.element('posPaymentStatus').value = 'partially paid';
    env.input.dataset.price = '1600.50';
    env.listeners['posForm:input']();
    assert.equal(env.element('posTotal').textContent, 'UGX 3,201.00');
    assert.equal(env.element('posAmountPaid').max, '3201.00');
});
