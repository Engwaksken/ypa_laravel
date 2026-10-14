const {test} = require('node:test');
const assert = require('node:assert/strict');
const {csv, csvCell, printHtml} = require('../../public/js/table-tools.js');
const fs = require('node:fs');
const vm = require('node:vm');

test('CSV escapes quotes, commas, formulas and leading control characters', () => {
    assert.equal(csvCell('a,"b"'), '"a,""b"""');
    for (const value of ['=1+1', '+SUM(A1)', '-1', '@test', '\t=1+1', '\r\n=1+1']) {
        assert.ok(csvCell(value).startsWith('"\''));
    }
    assert.equal(csv(['Name', 'Amount'], [['Jane, Farmer', '100']]), '\uFEFF"Name","Amount"\r\n"Jane, Farmer","100"\r\n');
});

test('PDF print document uses only supplied columns and text, never source HTML', () => {
    const html = printHtml('Report <script>', ['Name'], [['<img src=x onerror=alert(1)>']]);
    assert.ok(html.includes('&lt;script&gt;'));
    assert.ok(html.includes('&lt;img src=x onerror=alert(1)&gt;'));
    assert.ok(!html.includes('<img'));
    assert.ok(!html.includes('<script'));
    assert.ok(html.includes('Save as PDF'));
    assert.ok(html.includes('Loaded rows: 1'));
});

// Minimal DOM harness exercises the shipped script, not a second implementation.
class Element {
    constructor(tag, value = '') {
        this.tagName = tag.toUpperCase(); this.valueText = value; this.children = [];
        this.dataset = {}; this.attributes = {}; this.listeners = {}; this.hidden = false;
        this.colSpan = 1; this.rowSpan = 1; this.classes = new Set();
        this.classList = {contains: name => this.classes.has(name), toggle: (name, on) => on ? this.classes.add(name) : this.classes.delete(name)};
    }
    set className(value) { this.classes = new Set(value.split(' ')); }
    get textContent() { return this.valueText + this.children.map(child => child.textContent).join(''); }
    set textContent(value) { this.valueText = value; this.children = []; }
    get cells() { return this.children.filter(child => ['TH', 'TD'].includes(child.tagName)); }
    get rows() { return this.children.flatMap(child => child.tagName === 'TR' ? [child] : child.rows); }
    get tHead() { return this.children.find(child => child.tagName === 'THEAD'); }
    get tBodies() { return this.children.filter(child => child.tagName === 'TBODY'); }
    append(...nodes) { nodes.forEach(node => { node.remove(); node.parentElement = this; this.children.push(node); }); }
    before(node) { const parent = this.parentElement; node.remove(); node.parentElement = parent; parent.children.splice(parent.children.indexOf(this), 0, node); }
    remove() { if (this.parentElement) this.parentElement.children.splice(this.parentElement.children.indexOf(this), 1); this.parentElement = null; }
    setAttribute(key, value) { this.attributes[key] = value; }
    addEventListener(event, callback) { this.listeners[event] = callback; }
    click() { if (!this.disabled) this.listeners.click?.(); }
    matches(selector) {
        if (selector === 'main.main-content') return this.tagName === 'MAIN' && this.classes.has('main-content');
        if (selector.startsWith('.')) return this.classes.has(selector.slice(1));
        if (selector === '[data-table-tools="off"]') return this.dataset.tableTools === 'off';
        if (selector === '[data-export-ignore]') return this.dataset.exportIgnore !== undefined;
        return this.tagName === selector.toUpperCase();
    }
    closest(selectors) { for (let node = this; node; node = node.parentElement) if (selectors.split(',').some(selector => node.matches(selector.trim()))) return node; return null; }
    querySelectorAll(selectors) { return this.children.flatMap(child => [...(selectors.split(',').some(selector => child.matches(selector.trim())) ? [child] : []), ...child.querySelectorAll(selectors)]); }
    querySelector(selectors) { return this.querySelectorAll(selectors)[0] || null; }
    cloneNode() { const clone = new Element(this.tagName, this.valueText); clone.dataset = {...this.dataset}; clone.append(...this.children.map(child => child.cloneNode())); return clone; }
}

function environment({allowed = true, inForm = false, headerless = false} = {}) {
    const main = new Element('main'); main.className = 'main-content'; main.dataset.tableExport = String(allowed);
    const heading = new Element('h1', 'Members'); main.append(heading);
    const table = new Element('table');
    const thead = new Element('thead'); const header = new Element('tr');
    header.append(new Element('th', 'Name'), new Element('th', 'Amount'), new Element('th', 'Actions')); thead.append(header);
    const tbody = new Element('tbody'); const row = new Element('tr');
    row.append(new Element('td', 'Jane'), new Element('td', '=1+1'), new Element('td', 'Edit')); tbody.append(row);
    const empty = new Element('tr'); const emptyCell = new Element('td', 'No records'); emptyCell.colSpan = 3; empty.append(emptyCell); tbody.append(empty);
    if (headerless) {
        row.children[2].remove(); empty.remove();
        table.append(tbody);
    } else {
        table.append(thead, tbody);
    }
    const host = new Element(inForm ? 'form' : 'div'); host.append(table); main.append(host);
    const page = new Element('body'); page.append(main);
    const observers = []; const blobs = []; const popups = [];
    const document = {querySelector: selector => page.querySelector(selector), title: 'Members',
        createElement: tag => new Element(tag), createTextNode: value => new Element('text', value)};
    const context = {document, Blob, URL: {createObjectURL: blob => { blobs.push(blob); return 'blob:csv'; }, revokeObjectURL() {}},
        getComputedStyle: node => ({display: node.hidden ? 'none' : 'table-row'}), setTimeout: callback => callback(),
        MutationObserver: class { constructor(callback) { this.callback = callback; observers.push(this); } observe(target) { this.target = target; } },
        open() { const popup = {opener: {}, focus() {}, print() { this.printed = true; }, document: {
            open() {}, write(html) { this.html = html; }, close() {}, getElementById() { return new Element('button'); }
        }}; popups.push(popup); return popup; }};
    context.window = context;
    vm.runInNewContext(fs.readFileSync(require.resolve('../../public/js/table-tools.js'), 'utf8'), context);
    const button = label => main.querySelectorAll('button').find(node => node.textContent === label);
    return {main, table, tbody, row, emptyCell, button, observers, blobs, popups};
}

test('shared toolbar collapses and expands tables with accessible state', () => {
    const env = environment(); const collapse = env.button('Collapse table');
    collapse.click();
    assert.equal(collapse.textContent, 'Expand table');
    assert.equal(collapse.attributes['aria-expanded'], 'false');
    assert.equal(env.table.parentElement.hidden, true);
    collapse.click();
    assert.equal(collapse.attributes['aria-expanded'], 'true');
    assert.equal(env.table.parentElement.hidden, false);
});

test('selected columns control visibility, empty-row spans, CSV and PDF', async () => {
    const env = environment(); const inputs = env.main.querySelectorAll('input');
    inputs[1].checked = false; inputs[1].listeners.change();
    assert.equal(env.row.cells[1].classList.contains('ypa-table-column-hidden'), true);
    assert.equal(env.emptyCell.colSpan, 2);
    env.button('CSV').click();
    assert.equal(await env.blobs[0].text(), '"Name"\r\n"Jane"\r\n'); // Blob strips the UTF-8 BOM on text decoding.
    env.button('Print / PDF').click();
    assert.ok(env.popups[0].document.html.includes('<th>Name</th>'));
    assert.ok(!env.popups[0].document.html.includes('<th>Amount</th>'));
    assert.ok(!env.popups[0].document.html.includes('<th>Actions</th>'));
    assert.equal(env.popups[0].opener, null);
    env.button('Show all columns').click();
    assert.equal(env.emptyCell.colSpan, 3);
    assert.equal(env.row.cells[1].classList.contains('ypa-table-column-hidden'), false);
});

test('last visible column is protected and action-only exports are disabled', () => {
    const env = environment(); const inputs = env.main.querySelectorAll('input');
    for (const input of inputs.slice(0, 2)) { input.checked = false; input.listeners.change(); }
    assert.equal(inputs[2].disabled, true);
    assert.equal(env.button('CSV').disabled, true);
    assert.equal(env.button('Print / PDF').disabled, true);
});

test('permission-restricted pages retain column controls without export buttons', () => {
    const env = environment({allowed: false});
    assert.ok(env.button('Collapse table'));
    assert.equal(env.button('CSV'), undefined);
    assert.equal(env.button('Print / PDF'), undefined);
});

test('editable tables inside forms are not enhanced', () => {
    const env = environment({inForm: true});
    assert.equal(env.button('Collapse table'), undefined);
});

test('key/value detail tables without headings receive selectable Field and Value columns', () => {
    const env = environment({headerless: true});
    assert.deepEqual(env.table.tHead.rows[0].cells.map(cell => cell.textContent), ['Field', 'Value']);
    assert.ok(env.button('CSV'));
    assert.equal(env.main.querySelectorAll('input').length, 2);
});

test('newly loaded rows receive the current column visibility without duplicate toolbars', () => {
    const env = environment(); const input = env.main.querySelectorAll('input')[1];
    input.checked = false; input.listeners.change();
    const row = new Element('tr'); row.append(new Element('td', 'New'), new Element('td', '100'), new Element('td', 'Edit'));
    env.tbody.append(row);
    env.observers.find(observer => observer.target === env.table).callback();
    env.observers.find(observer => observer.target === env.main).callback();
    assert.equal(row.cells[1].classList.contains('ypa-table-column-hidden'), true);
    assert.equal(env.main.querySelectorAll('.ypa-table-tools').length, 1);
});
