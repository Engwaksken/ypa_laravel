const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '../..');
function script(view) {
    const source = fs.readFileSync(path.join(root, 'resources/views', view), 'utf8');
    return [...source.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)].map(match => match[1]).join('\n')
        .replace(/\{\{[\s\S]*?\}\}/g, '/endpoint')
        .replace(/@json\([^\n]+\)/g, '[]')
        .replace(/^\s*@(?:if|endif)[^\n]*$/gm, '');
}
function environment(view) {
    const elements = new Map();
    const calls = [];
    const element = id => {
        if (!elements.has(id)) elements.set(id, {value: '', disabled: false, dataset: {}, innerHTML: '', textContent: '',
            checkValidity: () => true, reportValidity: () => true, reset() {}, addEventListener() {}, getAttribute: () => 'token'});
        return elements.get(id);
    };
    class FormData {
        constructor() { this.values = new Map(); }
        set(key, value) { this.values.set(key, value); }
        append(key, value) { this.values.set(key, value); }
    }
    const context = {document: {getElementById: element, querySelector: () => ({content: 'token', getAttribute: () => 'token'}),
        querySelectorAll: () => [], body: {dataset: {}}, createElement: () => ({appendChild() {}, innerHTML: ''}), createTextNode: value => value},
        FormData, fetch: (url, options) => { calls.push({url, options}); return new Promise(() => {}); },
        bootstrap: {Modal: class { static getOrCreateInstance() { return {show() {}, hide() {}}; } show() {} }},
        alert() {}, confirm: () => true, setTimeout() {}, location: {reload() {}}};
    context.window = context;
    vm.createContext(context);
    vm.runInContext(script(view), context);
    return {context, element, calls};
}
for (const [view, handler, field] of [
    ['projects/index.blade.php', 'submitProjectForm', 'project_id'],
    ['project-categories/index.blade.php', 'saveCategory', 'category_id'],
    ['expenses/index.blade.php', 'saveExpense', 'expenseId'],
]) {
    test(view + ' submits edits as POST with PUT override', () => {
        const env = environment(view);
        env.element(field).value = '42';
        if (field === 'project_id') env.element('project_code').value = 'PRJ-42';
        env.context[handler]();
        assert.equal(env.calls.length, 1);
        assert.equal(env.calls[0].options.method, 'POST');
        assert.equal(env.calls[0].options.body.values.get('_method'), 'PUT');
        if (field === 'project_id') assert.equal(env.calls[0].options.body.values.get('project_code'), 'PRJ-42');
    });
}
test('meeting saves are blocked until lists finish loading', () => {
    const env = environment('meetings/show.blade.php');
    env.element('saveInvitesButton').disabled = true;
    env.element('saveAttendanceButton').disabled = true;
    env.context.saveInviteSelection();
    env.context.saveAttendance();
    assert.equal(env.calls.length, 0);
    env.element('saveInvitesButton').disabled = false;
    env.context.saveInviteSelection();
    env.context.saveInviteSelection();
    assert.equal(env.calls.length, 1);
});
test('checkout empty cart opens a useful result without posting', async () => {
    const env = environment('orders/place.blade.php');
    // Re-run with the form listener captured.
    let submit;
    env.element('orderForm').addEventListener = (name, handler) => { submit = handler; };
    vm.runInContext(script('orders/place.blade.php'), env.context);
    await submit({preventDefault() {}});
    assert.equal(env.calls.length, 0);
    assert.equal(env.element('orderResultTitle').textContent, 'Select products');
});
for (const external of [false, true]) {
    test('modal validation recovery ' + (external ? 'rejects external action' : 'preserves edit values and unchecked flags'), () => {
        const source = fs.readFileSync(path.join(root, 'resources/views/layouts/partials/modal-validation.blade.php'), 'utf8');
        const old = {_modal_form: 'productForm', _modal_action: external ? 'https://other.example/products/42' : 'https://ypa.test/products/42', _method: 'PUT', name: 'Correct me', is_active: '0'};
        const code = source.match(/<script>([\s\S]*?)<\/script>/)[1]
            .replace(/\{\{ Illuminate\\Support\\Js::from\(session\(\)->getOldInput\(\)\) \}\}/, JSON.stringify(old))
            .replace(/\{\{ Illuminate\\Support\\Js::from\(\$errors->all\(\)\) \}\}/, JSON.stringify(['Name is required.']));
        let shown = 0;
        const fields = [{name: '_method', type: 'hidden', value: 'POST'}, {name: 'name', type: 'text', value: ''},
            {name: 'is_active', type: 'hidden', value: '0'}, {name: 'is_active', type: 'checkbox', value: '1', checked: true}];
        const title = {textContent: 'Add Product'};
        const body = {prepend() {}};
        const modal = {querySelector: selector => selector === '.modal-title' ? title : body};
        const form = {id: 'productForm', action: 'https://ypa.test/products', elements: fields, addEventListener() {}, closest: () => modal};
        const context = {URL, location: {href: 'https://ypa.test/products', origin: 'https://ypa.test'},
            document: {querySelectorAll: () => [form], createElement: () => ({setAttribute() {}})},
            bootstrap: {Modal: {getOrCreateInstance: () => ({show() {shown++;}})}}};
        vm.runInNewContext(code, context);
        assert.equal(shown, external ? 0 : 1);
        if (!external) {
            assert.equal(fields[0].value, 'PUT');
            assert.equal(fields[1].value, 'Correct me');
            assert.equal(fields[2].value, '0');
            assert.equal(fields[3].checked, false);
            assert.equal(title.textContent, 'Edit Product');
        }
    });
}
test('checkout blocks overlapping submissions while a request is pending', async () => {
    const env = environment('orders/place.blade.php');
    let submit;
    env.element('orderForm').addEventListener = (name, handler) => { submit = handler; };
    env.context.document.querySelectorAll = () => [{value: '2', dataset: {id: '42'}, reportValidity: () => true}];
    vm.runInContext(script('orders/place.blade.php'), env.context);
    submit({preventDefault() {}});
    await submit({preventDefault() {}});
    assert.equal(env.calls.length, 1);
    assert.equal(env.element('orderSubmit').disabled, true);
});
test('order status modal resets to valid transitions for each order', () => {
    const env = environment('orders/index.blade.php');
    let show;
    const select = {options: ['processing', 'completed', 'cancelled'].map(value => ({value})), value: ''};
    env.element('statusForm').querySelector = () => select;
    env.element('statusModal').addEventListener = (name, handler) => { show = handler; };
    vm.runInContext(script('orders/index.blade.php'), env.context);
    show({relatedTarget: {dataset: {order: 'A', action: '/orders/1/status', status: 'processing'}}});
    assert.equal(select.value, 'completed');
    assert.equal(select.options[0].disabled, true);
    show({relatedTarget: {dataset: {order: 'B', action: '/orders/2/status', status: 'pending'}}});
    assert.equal(select.value, 'processing');
    assert.equal(select.options[1].disabled, true);
    assert.equal(env.element('statusForm').action, '/orders/2/status');
});

for (const [view, handler, button] of [
    ['projects/index.blade.php', 'submitProjectForm', 'saveBtn'],
    ['project-categories/index.blade.php', 'saveCategory', 'categorySaveBtn'],
    ['expenses/index.blade.php', 'saveExpense', 'expenseSaveBtn'],
]) {
    test(view + ' blocks duplicate saves and saves during loading', () => {
        const env = environment(view);
        env.element(button).disabled = true;
        env.context[handler]();
        assert.equal(env.calls.length, 0);
        env.element(button).disabled = false;
        env.context[handler]();
        env.context[handler]();
        assert.equal(env.calls.length, 1);
    });
}

test('project edit retains its target after reset and ignores a response after switching to create', async () => {
    const env = environment('projects/index.blade.php');
    const pending = [];
    env.context.fetch = () => new Promise(resolve => { pending.push(resolve); });
    env.element('projectForm').reset = () => { env.element('project_id').value = ''; };
    env.context.editProject('42');
    assert.equal(env.element('project_id').value, '42');
    assert.equal(env.element('saveBtn').disabled, true);
    env.context.openCreateModal();
    pending[0]({json: async () => ({success: true, project: {id: 42, project_name: 'Old response'}})});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(env.element('project_id').value, '');
    assert.notEqual(env.element('project_name').value, 'Old response');
    assert.equal(env.element('saveBtn').disabled, false);
});

test('category ignores old edit responses after a new edit is chosen', async () => {
    const env = environment('project-categories/index.blade.php');
    const pending = [];
    env.context.fetch = () => new Promise(resolve => { pending.push(resolve); });
    env.context.editCategory('1');
    env.context.editCategory('2');
    pending[0]({json: async () => ({success: true, category: {category_name: 'First'}})});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(env.element('categorySaveBtn').disabled, true);
    assert.notEqual(env.element('category_name').value, 'First');
    pending[1]({json: async () => ({success: true, category: {category_name: 'Second'}})});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(env.element('category_name').value, 'Second');
    assert.equal(env.element('category_id').value, '2');
    assert.equal(env.element('categorySaveBtn').disabled, false);
});

test('ordinary modal POST prevents duplicate submission and restores buttons on back navigation', () => {
    const source = fs.readFileSync(path.join(root, 'resources/views/layouts/partials/modal-validation.blade.php'), 'utf8');
    const code = source.match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace(/\{\{ Illuminate\\Support\\Js::from\(session\(\)->getOldInput\(\)\) \}\}/, '{}')
        .replace(/\{\{ Illuminate\\Support\\Js::from\(\$errors->all\(\)\) \}\}/, '[]');
    let submit, pageshow;
    const button = {disabled: false, dataset: {}};
    const inputs = {};
    const form = {id: 'productForm', action: '/products', dataset: {},
        addEventListener: (name, handler) => { submit = handler; },
        querySelector: selector => inputs[selector],
        appendChild: input => { inputs['input[name="' + input.name + '"]'] = input; },
        querySelectorAll: selector => selector === '[data-submit-locked]' ? (button.dataset.submitLocked ? [button] : []) : [button]};
    vm.runInNewContext(code, {window: {addEventListener: (name, handler) => { pageshow = handler; }},
        document: {querySelectorAll: () => [form], createElement: () => ({})}});
    submit({defaultPrevented: false, preventDefault() { assert.fail('First submission must be allowed'); }});
    assert.equal(button.disabled, true);
    let prevented = false;
    submit({defaultPrevented: false, preventDefault() { prevented = true; }});
    assert.equal(prevented, true);
    pageshow();
    assert.equal(button.disabled, false);
    assert.equal(form.dataset.submitting, undefined);
});
