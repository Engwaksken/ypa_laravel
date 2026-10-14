const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function node(tag = 'div', attrs = {}) {
    const values = new Map(Object.entries(attrs));
    return {tagName: tag.toUpperCase(), childNodes: [], textContent: '', listeners: {},
        get attributes() { return [...values].map(([name, value]) => ({name, value})); },
        setAttribute(name, value) { values.set(name, value); }, getAttribute(name) { return values.get(name) || null; },
        hasAttribute(name) { return values.has(name); }, removeAttribute(name) { values.delete(name); },
        addEventListener(name, callback) { this.listeners[name] = callback; },
        append(...children) { this.childNodes.push(...children); }, replaceChildren(...children) { this.childNodes = children; },
        remove() { this.removed = true; }, replaceWith(replacement) { this.removed = true; this.replacement = replacement; }};
}

function environment() {
    const main = node(); const modal = node(); const body = node(); const title = node(); const fullPage = node('a');
    const ids = {tableViewModal: modal, tableViewModalBody: body, tableViewModalTitle: title, tableViewFullPage: fullPage};
    const requests = []; const shows = []; const documents = [];
    const context = {URL, AbortController, location: {origin: 'https://ypa.test', href: 'https://ypa.test/contracts'},
        document: {querySelector: () => main, getElementById: id => ids[id], createElement: tag => node(tag)},
        bootstrap: {Modal: {getOrCreateInstance: () => ({show: trigger => shows.push(trigger)})}},
        fetch(url, options) { return new Promise((resolve, reject) => requests.push({url, options, resolve, reject})); },
        DOMParser: class { parseFromString(html) {
            if (html === 'NO_MAIN') return {querySelector: () => null};
            const text = node('p', {id: 'collision', onclick: 'danger()', style: 'color:red', class: 'detail'});
            text.textContent = html;
            const script = node('script');
            const link = node('a', {href: 'javascript:danger()'}); link.append(node('span'));
            const image = node('img', {src: '/uploads/photo.jpg', onerror: 'danger()'});
            const externalImage = node('img', {src: 'https://other.test/tracker'});
            const nodes = [text, script, link, image, externalImage];
            const content = {childNodes: [text], querySelectorAll(selector) {
                if (selector === '*') return nodes.filter(item => !item.removed);
                if (selector === 'a') return [link];
                return [script];
            }};
            const parsed = {querySelector: () => content, createElement: tag => node(tag)};
            documents.push({text, script, link, image, externalImage});
            return parsed;
        }}
    };
    context.window = context;
    vm.runInNewContext(fs.readFileSync(require.resolve('../../public/js/table-view-modal.js'), 'utf8'), context);
    function click({href = 'https://ypa.test/contracts/1', label = 'View contract CT-001', table = true, ...options} = {}) {
        const link = node('a', {'aria-label': label, ...(options.attributes || {})});
        link.href = href; link.target = options.target || '';
        link.closest = selector => selector === 'table' && table ? {} : null;
        link.querySelector = () => null;
        const event = {button: 0, target: {closest: () => link}, preventDefault() { this.prevented = true; }, ...options};
        main.listeners.click(event);
        return {event, link};
    }
    return {main, modal, body, title, fullPage, requests, shows, documents, click};
}
const flush = () => new Promise(resolve => setImmediate(resolve));
function respond(request, html, overrides = {}) {
    request.resolve({ok: true, status: 200, redirected: false, headers: {get: () => 'text/html; charset=UTF-8'},
        text: async () => html, ...overrides});
}

test('table View links open the shared modal and load an authenticated no-cache preview', async () => {
    const env = environment(); const {event, link} = env.click();
    assert.equal(event.prevented, true);
    assert.equal(env.shows[0], link);
    assert.equal(env.title.textContent, 'View contract CT-001');
    assert.equal(env.fullPage.href, link.href);
    assert.equal(env.body.getAttribute('aria-busy'), 'true');
    assert.equal(env.requests[0].options.credentials, 'same-origin');
    assert.equal(env.requests[0].options.cache, 'no-store');
    respond(env.requests[0], 'Contract document'); await flush();
    assert.equal(env.body.childNodes[0].textContent, 'Contract document');
    assert.equal(env.body.getAttribute('aria-busy'), 'false');
});

test('previews strip actions, scripts, IDs, events, links and external images', async () => {
    const env = environment(); env.click(); respond(env.requests[0], 'Details'); await flush();
    const doc = env.documents[0];
    assert.equal(doc.script.removed, true);
    assert.equal(doc.link.replacement.tagName, 'SPAN');
    for (const attribute of ['id', 'onclick', 'style']) assert.equal(doc.text.hasAttribute(attribute), false);
    assert.equal(doc.text.getAttribute('class'), 'detail');
    assert.equal(doc.image.getAttribute('src'), 'https://ypa.test/uploads/photo.jpg');
    assert.equal(doc.image.hasAttribute('onerror'), false);
    assert.equal(doc.externalImage.hasAttribute('src'), false);
});

test('rapid row changes cannot render stale responses', async () => {
    const env = environment(); env.click(); env.click({href: 'https://ypa.test/contracts/2', label: 'View CT-002'});
    assert.equal(env.requests[0].options.signal.aborted, true);
    respond(env.requests[1], 'Second contract'); await flush();
    respond(env.requests[0], 'First contract'); await flush();
    assert.equal(env.body.childNodes[0].textContent, 'Second contract');
    assert.equal(env.title.textContent, 'View CT-002');
});

test('closing the modal cancels loading and clears private preview data', async () => {
    const env = environment(); env.click(); env.modal.listeners['hide.bs.modal']();
    assert.equal(env.requests[0].options.signal.aborted, true);
    respond(env.requests[0], 'Late private content'); await flush();
    assert.equal(env.body.childNodes.length, 0);
    assert.equal(env.body.getAttribute('aria-busy'), 'false');
});

test('permission failures and login redirects show a safe error with the full-page fallback', async () => {
    for (const overrides of [{ok: false, status: 403}, {redirected: true}, {headers: {get: () => 'application/json'}}]) {
        const env = environment(); env.click(); respond(env.requests[0], 'Not a preview', overrides); await flush();
        assert.equal(env.body.childNodes[0].getAttribute('role'), 'alert');
        assert.ok(env.fullPage.href.endsWith('/contracts/1'));
        assert.equal(env.documents.length, 0);
    }
});

test('missing detail content and network errors are recoverable without replacing the page', async () => {
    const env = environment(); env.click(); respond(env.requests[0], 'NO_MAIN'); await flush();
    assert.equal(env.body.childNodes[0].getAttribute('role'), 'alert');
    env.click(); env.requests[1].reject(new Error('Network unavailable')); await flush();
    assert.equal(env.body.childNodes[0].textContent, 'Network unavailable');
});

test('modified clicks, non-table links, existing modal links and external URLs keep normal navigation', () => {
    for (const options of [{ctrlKey: true}, {button: 1}, {table: false}, {label: 'Edit contract'},
        {href: 'https://other.test/contracts/1'}, {href: 'javascript:alert(1)'}, {target: '_blank'},
        {attributes: {'data-bs-toggle': 'modal'}}, {attributes: {download: 'document.pdf'}}]) {
        const env = environment(); const {event} = env.click(options);
        assert.equal(event.prevented, undefined);
        assert.equal(env.requests.length, 0);
    }
});
