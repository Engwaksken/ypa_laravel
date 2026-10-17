/*
 * YPA UI kit: progressive enhancement for every form on the page.
 *  - Searchable dropdowns for single-choice <select> fields. The native select
 *    stays in the DOM, so form submission, validation and existing change
 *    listeners keep working exactly as before.
 *  - Consistent placeholders and hints derived from labels and constraints.
 *  - Required-field markers and live character counters.
 *
 * Opt-outs: data-ui-search="off" on a select keeps the native control,
 * data-ui-placeholder="off" / data-ui-hint="off" skip the text helpers.
 * Opt-ins: data-hint="..." renders a hint under any field.
 */
(function (root) {
    'use strict';

    const doc = root.document;
    const SEARCH_THRESHOLD = 7;
    const enhanced = new WeakMap();
    let uid = 0;
    let openInstance = null;

    const nativeValue = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    const nativeIndex = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');

    function labelText(field) {
        let label = field.labels && field.labels[0];
        if (!label) {
            const group = field.closest('.form-group, .mb-3, .mb-2, [class*="col-"]');
            label = group && group.querySelector('label:not(.form-check-label)');
        }
        const text = (label ? label.textContent : field.getAttribute('aria-label') || '')
            .replace(/[*:]+/g, ' ').replace(/\(.*?\)/g, ' ').replace(/\s+/g, ' ').trim();
        return text;
    }

    function lowerFirst(text) {
        return /^[A-Z][a-z]/.test(text) ? text.charAt(0).toLowerCase() + text.slice(1) : text;
    }

    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, ch => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[ch]));
    }

    function normalize(text) {
        return String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    /* ---------------------------------------------------------------
     * Searchable select
     * ------------------------------------------------------------- */

    function SearchableSelect(select) {
        this.select = select;
        this.id = 'ui-select-' + (++uid);
        this.activeIndex = -1;
        this.items = [];
        this.build();
        this.patchValueSetters();
        this.observe();
        this.sync();
    }

    SearchableSelect.prototype.build = function () {
        const select = this.select;
        const wrapper = doc.createElement('div');
        wrapper.className = 'ui-select';
        // Layout utilities (width, margin, flex, grid) belong on the visible wrapper.
        const measured = select.offsetWidth;
        Array.from(select.classList).forEach(name => {
            if (/^(w-\S+|mw-\S+|m[trblsexy]?-\S+|flex-\S+|col(-\S+)?|order-\S+|align-self-\S+|d-inline\S*)$/.test(name)) {
                wrapper.classList.add(name);
                select.classList.remove(name);
            }
        });
        ['width', 'minWidth', 'maxWidth', 'flex'].forEach(prop => {
            if (select.style[prop]) { wrapper.style[prop] = select.style[prop]; select.style[prop] = ''; }
        });
        if (wrapper.classList.contains('w-auto') && measured > 0) wrapper.style.minWidth = 'min(' + measured + 'px, 100%)';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('ui-select-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        const trigger = doc.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ui-select-trigger form-select';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-controls', this.id + '-list');
        trigger.innerHTML = '<span class="ui-select-value"></span>';
        const label = labelText(select);
        if (label) trigger.setAttribute('aria-label', label);
        wrapper.insertBefore(trigger, select);

        this.wrapper = wrapper;
        this.trigger = trigger;
        this.valueNode = trigger.firstChild;

        trigger.addEventListener('click', () => (this.isOpen() ? this.close(true) : this.open()));
        trigger.addEventListener('keydown', event => this.onTriggerKey(event));
        select.addEventListener('change', () => this.sync());
        select.addEventListener('invalid', () => trigger.classList.add('is-invalid'));
        select.addEventListener('focus', () => trigger.focus());
        Array.from(select.labels || []).forEach(node => node.addEventListener('click', event => {
            event.preventDefault();
            trigger.focus();
        }));
        if (select.form) select.form.addEventListener('reset', () => root.setTimeout(() => this.sync(), 0));
    };

    SearchableSelect.prototype.patchValueSetters = function () {
        // Scripts often set select.value directly, which fires no event; keep the label in step.
        const self = this;
        [['value', nativeValue], ['selectedIndex', nativeIndex]].forEach(([prop, descriptor]) => {
            Object.defineProperty(self.select, prop, {
                configurable: true,
                get() { return descriptor.get.call(this); },
                set(value) { descriptor.set.call(this, value); self.sync(); },
            });
        });
    };

    SearchableSelect.prototype.observe = function () {
        this.observer = new MutationObserver(() => this.sync());
        this.observer.observe(this.select, {
            childList: true, subtree: true, characterData: true,
            attributes: true, attributeFilter: ['disabled', 'hidden', 'style', 'class', 'selected', 'required'],
        });
    };

    SearchableSelect.prototype.placeholderText = function () {
        const first = this.select.options[0];
        if (first && first.value === '' && first.textContent.trim() !== '') return first.textContent.trim();
        const label = labelText(this.select);
        return label ? 'Select ' + lowerFirst(label) : 'Select an option';
    };

    SearchableSelect.prototype.sync = function () {
        const select = this.select;
        const option = select.options[nativeIndex.get.call(select)];
        const empty = !option || option.value === '';
        const text = option && option.textContent.trim() !== '' ? option.textContent.trim() : this.placeholderText();

        this.valueNode.textContent = empty && (!option || option.textContent.trim() === '' || /^[-\s]*$/.test(option.textContent))
            ? this.placeholderText() : text;
        this.trigger.classList.toggle('is-placeholder', empty);
        this.trigger.disabled = select.disabled;
        this.trigger.title = empty ? '' : text;

        ['is-invalid', 'is-valid', 'form-select-sm', 'form-select-lg'].forEach(name => {
            this.trigger.classList.toggle(name, select.classList.contains(name));
        });
        if (select.checkValidity && select.checkValidity() && !select.classList.contains('is-invalid')) {
            this.trigger.classList.remove('is-invalid');
        }

        const hidden = select.hidden || select.style.display === 'none' || select.classList.contains('d-none');
        this.wrapper.hidden = hidden;
        if (this.isOpen()) this.render(this.search ? this.search.value : '');
    };

    SearchableSelect.prototype.isOpen = function () {
        return Boolean(this.panel && !this.panel.hidden);
    };

    SearchableSelect.prototype.ensurePanel = function () {
        if (this.panel) return;
        const panel = doc.createElement('div');
        panel.className = 'ui-select-panel';
        panel.hidden = true;
        panel.innerHTML =
            '<div class="ui-select-search"><i class="fas fa-search" aria-hidden="true"></i>' +
            '<input type="search" autocomplete="off" spellcheck="false" placeholder="Type to search..." ' +
            'aria-label="Search options" role="combobox" aria-autocomplete="list" aria-expanded="true"></div>' +
            '<ul class="ui-select-list" role="listbox" id="' + this.id + '-list"></ul>' +
            '<div class="ui-select-empty" hidden>No matching options</div>';
        this.panel = panel;
        this.search = panel.querySelector('input');
        this.list = panel.querySelector('ul');
        this.empty = panel.querySelector('.ui-select-empty');
        this.search.setAttribute('aria-controls', this.id + '-list');

        this.search.addEventListener('input', () => this.render(this.search.value));
        this.search.addEventListener('keydown', event => this.onListKey(event));
        this.list.addEventListener('keydown', event => this.onListKey(event));
        this.list.addEventListener('mousedown', event => event.preventDefault());
        this.list.addEventListener('click', event => {
            const node = event.target.closest('.ui-select-option');
            if (node) this.choose(Number(node.dataset.index));
        });
        this.list.addEventListener('mousemove', event => {
            const node = event.target.closest('.ui-select-option');
            if (node && node.getAttribute('aria-disabled') !== 'true') this.setActive(Number(node.dataset.pos));
        });
    };

    SearchableSelect.prototype.optionCount = function () {
        return Array.from(this.select.options).filter(opt => !opt.hidden && opt.style.display !== 'none').length;
    };

    SearchableSelect.prototype.open = function (initialQuery) {
        if (this.select.disabled) return;
        if (openInstance && openInstance !== this) openInstance.close(false);
        this.ensurePanel();
        // Inside a Bootstrap modal the panel must live in the modal or its focus trap steals focus.
        const host = this.wrapper.closest('.modal, dialog, .offcanvas') || doc.body;
        if (this.panel.parentNode !== host) host.appendChild(this.panel);

        const searchable = this.select.dataset.uiSearch === 'on' || this.optionCount() >= SEARCH_THRESHOLD;
        this.panel.querySelector('.ui-select-search').hidden = !searchable;
        this.search.value = searchable ? (initialQuery || '') : '';
        this.panel.hidden = false;
        this.wrapper.classList.add('is-open');
        this.trigger.setAttribute('aria-expanded', 'true');
        openInstance = this;

        const sheet = root.matchMedia && root.matchMedia('(max-width: 575.98px)').matches;
        this.panel.classList.toggle('is-sheet', Boolean(sheet));
        if (sheet) {
            this.backdrop = doc.createElement('div');
            this.backdrop.className = 'ui-select-backdrop';
            this.backdrop.addEventListener('click', () => this.close(true));
            host.insertBefore(this.backdrop, this.panel);
        }

        this.render(this.search.value);
        this.position();
        if (searchable && !sheet) this.search.focus({preventScroll: true});
        else if (searchable && sheet) this.list.focus({preventScroll: true});
        else this.list.focus({preventScroll: true});
        this.list.tabIndex = -1;
        this.scrollActiveIntoView();
    };

    SearchableSelect.prototype.close = function (refocus) {
        if (!this.panel || this.panel.hidden) return;
        this.panel.hidden = true;
        this.wrapper.classList.remove('is-open');
        this.trigger.setAttribute('aria-expanded', 'false');
        if (this.backdrop) { this.backdrop.remove(); this.backdrop = null; }
        if (openInstance === this) openInstance = null;
        if (refocus) this.trigger.focus({preventScroll: true});
    };

    SearchableSelect.prototype.position = function () {
        if (this.panel.classList.contains('is-sheet')) return;
        const rect = this.trigger.getBoundingClientRect();
        const viewportH = root.innerHeight;
        const below = viewportH - rect.bottom - 8;
        const above = rect.top - 8;
        const panel = this.panel;
        panel.style.left = Math.max(8, Math.min(rect.left, root.innerWidth - Math.max(rect.width, 192) - 8)) + 'px';
        panel.style.width = Math.max(rect.width, 192) + 'px';
        const preferred = Math.min(320, panel.scrollHeight);
        if (below < preferred && above > below) {
            panel.style.maxHeight = Math.min(320, above) + 'px';
            panel.style.top = 'auto';
            panel.style.bottom = (viewportH - rect.top + 4) + 'px';
        } else {
            panel.style.maxHeight = Math.max(160, Math.min(320, below)) + 'px';
            panel.style.top = (rect.bottom + 4) + 'px';
            panel.style.bottom = 'auto';
        }
    };

    SearchableSelect.prototype.render = function (query) {
        const terms = normalize(query || '').split(/\s+/).filter(Boolean);
        const selectedIndex = nativeIndex.get.call(this.select);
        const html = [];
        this.items = [];
        let lastGroup = null;

        Array.from(this.select.options).forEach((option, index) => {
            if (option.hidden || option.style.display === 'none') return;
            const text = option.textContent.trim();
            const group = option.parentElement && option.parentElement.tagName === 'OPTGROUP' ? option.parentElement : null;
            const haystack = normalize(text + ' ' + (group ? group.label : ''));
            if (terms.length && (option.value === '' || !terms.every(term => haystack.includes(term)))) return;

            if (group !== lastGroup && group) html.push('<li class="ui-select-group" role="presentation">' + escapeHtml(group.label) + '</li>');
            lastGroup = group;

            const disabled = option.disabled || (group && group.disabled);
            const placeholder = option.value === '';
            let label = escapeHtml(text || this.placeholderText());
            terms.forEach(term => {
                const pattern = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
                label = label.replace(pattern, '<mark>$1</mark>');
            });
            const pos = this.items.length;
            this.items.push({index, disabled});
            html.push('<li class="ui-select-option' + (group ? ' is-grouped' : '') + (placeholder ? ' is-placeholder-option' : '') +
                '" role="option" id="' + this.id + '-opt-' + pos + '" data-index="' + index + '" data-pos="' + pos +
                '" aria-selected="' + (index === selectedIndex) + '"' + (disabled ? ' aria-disabled="true"' : '') + '><span class="ui-select-label">' + label + '</span></li>');
        });

        this.list.innerHTML = html.join('');
        this.empty.hidden = this.items.length > 0;
        const current = this.items.findIndex(item => item.index === selectedIndex);
        this.setActive(terms.length ? this.firstEnabled(0, 1) : (current >= 0 ? current : this.firstEnabled(0, 1)));
    };

    SearchableSelect.prototype.firstEnabled = function (start, step) {
        for (let pos = start; pos >= 0 && pos < this.items.length; pos += step) {
            if (!this.items[pos].disabled) return pos;
        }
        return -1;
    };

    SearchableSelect.prototype.setActive = function (pos) {
        const previous = this.list.querySelector('.is-active');
        if (previous) previous.classList.remove('is-active');
        this.activeIndex = pos;
        const node = pos >= 0 ? this.list.querySelector('[data-pos="' + pos + '"]') : null;
        if (node) node.classList.add('is-active');
        const owner = this.search && !this.search.parentNode.hidden ? this.search : this.list;
        if (node) owner.setAttribute('aria-activedescendant', node.id);
        else owner.removeAttribute('aria-activedescendant');
    };

    SearchableSelect.prototype.scrollActiveIntoView = function () {
        const node = this.list.querySelector('.is-active');
        if (node) node.scrollIntoView({block: 'nearest'});
    };

    SearchableSelect.prototype.move = function (step) {
        if (!this.items.length) return;
        let pos = this.activeIndex;
        for (let i = 0; i < this.items.length; i++) {
            pos = (pos + step + this.items.length) % this.items.length;
            if (!this.items[pos].disabled) break;
        }
        this.setActive(pos);
        this.scrollActiveIntoView();
    };

    SearchableSelect.prototype.choose = function (index) {
        const option = this.select.options[index];
        if (!option || option.disabled) return;
        const changed = nativeIndex.get.call(this.select) !== index;
        nativeIndex.set.call(this.select, index);
        this.sync();
        this.close(true);
        if (changed) {
            this.trigger.classList.remove('is-invalid');
            this.select.dispatchEvent(new Event('input', {bubbles: true}));
            this.select.dispatchEvent(new Event('change', {bubbles: true}));
        }
    };

    SearchableSelect.prototype.onTriggerKey = function (event) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key) && !event.altKey) {
            event.preventDefault();
            this.open();
        } else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
            event.preventDefault();
            this.open(event.key);
        }
    };

    SearchableSelect.prototype.onListKey = function (event) {
        switch (event.key) {
        case 'ArrowDown': event.preventDefault(); this.move(1); break;
        case 'ArrowUp': event.preventDefault(); this.move(-1); break;
        case 'Home': if (event.target === this.list) { event.preventDefault(); this.setActive(this.firstEnabled(0, 1)); this.scrollActiveIntoView(); } break;
        case 'End': if (event.target === this.list) { event.preventDefault(); this.setActive(this.firstEnabled(this.items.length - 1, -1)); this.scrollActiveIntoView(); } break;
        case 'Enter':
            event.preventDefault();
            if (this.activeIndex >= 0) this.choose(this.items[this.activeIndex].index);
            break;
        case 'Escape': event.preventDefault(); event.stopPropagation(); this.close(true); break;
        case 'Tab': this.close(false); break;
        default:
            if (event.target === this.list && event.key.length === 1 && !this.search.parentNode.hidden) {
                this.search.focus();
            }
        }
    };

    function shouldEnhanceSelect(select) {
        return select.matches('select.form-select, select.form-control') && !select.multiple &&
            !(select.size > 1) && select.dataset.uiSearch !== 'off' && !select.closest('[data-ui-search="off"]');
    }

    function enhanceSelect(select) {
        if (enhanced.has(select) || !shouldEnhanceSelect(select)) return;
        // A cloned row copies our wrapper markup but not its listeners: unwrap and rebuild.
        const wrapper = select.parentElement;
        if (wrapper && wrapper.classList.contains('ui-select')) {
            wrapper.querySelectorAll(':scope > .ui-select-trigger').forEach(node => node.remove());
            select.classList.remove('ui-select-native');
            select.removeAttribute('aria-hidden');
            select.removeAttribute('tabindex');
            wrapper.parentNode.insertBefore(select, wrapper);
            wrapper.remove();
        }
        enhanced.set(select, new SearchableSelect(select));
    }

    /* ---------------------------------------------------------------
     * Placeholders, hints, required markers and counters
     * ------------------------------------------------------------- */

    const TEXT_TYPES = ['text', 'email', 'tel', 'number', 'search', 'url', 'password', ''];

    function placeholderFor(field) {
        const label = labelText(field);
        const subject = label ? lowerFirst(label) : '';
        const type = (field.getAttribute('type') || '').toLowerCase();
        const name = (field.name || '').toLowerCase();
        if (type === 'email' || /email/.test(name)) return 'e.g. name@example.com';
        if (type === 'tel' || /phone|mobile|contact_no|telephone/.test(name)) return 'e.g. +256712345678';
        if (type === 'url' || /(^|_)url$|website/.test(name)) return 'https://';
        if (type === 'password') return label ? 'Enter ' + subject : 'Enter password';
        if (type === 'search') return label ? 'Search ' + subject + '...' : 'Search...';
        if (type === 'number') {
            if (/amount|price|cost|total|balance|fee|salary|paid/.test(name + ' ' + subject)) return '0.00';
            return field.min !== '' && field.min !== undefined ? 'e.g. ' + field.min : '0';
        }
        if (field.tagName === 'TEXTAREA') return label ? 'Enter ' + subject + '...' : 'Type here...';
        return label ? 'Enter ' + subject : '';
    }

    function formatBytes(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(bytes % 1048576 ? 1 : 0) + ' MB' : Math.round(bytes / 1024) + ' KB';
    }

    function autoHint(field) {
        if (field.dataset.hint) return {text: field.dataset.hint, icon: 'fa-circle-info'};
        if (field.dataset.uiHint === 'off') return null;
        const type = (field.getAttribute('type') || '').toLowerCase();
        const name = (field.name || '').toLowerCase();

        if (type === 'file') {
            const accept = (field.getAttribute('accept') || '').trim();
            const parts = [];
            if (accept === 'image/*') parts.push('Images only (JPG, PNG or WEBP)');
            else if (accept) parts.push('Accepted: ' + accept.split(',').map(s => s.trim().replace(/^\./, '').replace(/^.*\//, '').toUpperCase()).join(', '));
            if (field.dataset.maxSize) parts.push('max ' + formatBytes(Number(field.dataset.maxSize)));
            return parts.length ? {text: parts.join(', ') + '.', icon: 'fa-paperclip'} : null;
        }
        if (field.pattern && field.title) return {text: field.title, icon: 'fa-circle-info'};
        if (type === 'tel' || /phone|mobile|telephone/.test(name)) {
            return {text: 'Digits only, 7 to 15 numbers. Include the country code, e.g. +256.', icon: 'fa-phone'};
        }
        if (type === 'password' && field.minLength > 0) {
            return {text: 'Use at least ' + field.minLength + ' characters.', icon: 'fa-lock'};
        }
        if (type === 'number' && (field.min !== '' || field.max !== '')) {
            if (field.min !== '' && field.max !== '') return {text: 'Enter a value from ' + field.min + ' to ' + field.max + '.', icon: 'fa-circle-info'};
            if (field.min !== '' && Number(field.min) > 0) return {text: 'Minimum value is ' + field.min + '.', icon: 'fa-circle-info'};
        }
        return null;
    }

    function hintAnchor(field) {
        const group = field.closest('.input-group, .ui-select');
        return group || field;
    }

    function hasExistingHint(field) {
        let next = hintAnchor(field).nextElementSibling;
        while (next) {
            if (next.matches('.form-text, .ui-hint')) return true;
            if (next.matches('input, select, textarea, .input-group, .ui-select, label')) return false;
            next = next.nextElementSibling;
        }
        return false;
    }

    function insertAfterAnchor(field, node) {
        const anchor = hintAnchor(field);
        let after = anchor;
        while (after.nextElementSibling && after.nextElementSibling.matches('.invalid-feedback, .valid-feedback')) {
            after = after.nextElementSibling;
        }
        after.insertAdjacentElement('afterend', node);
    }

    function addCounter(field) {
        const max = field.maxLength;
        if (!(max > 0) || field.dataset.uiCounter === 'off') return;
        if (field.tagName !== 'TEXTAREA' && field.dataset.uiCounter !== 'on') return;
        let hint = field.__uiHint;
        if (!hint) {
            hint = doc.createElement('div');
            hint.className = 'ui-hint';
            insertAfterAnchor(field, hint);
            field.__uiHint = hint;
        }
        const counter = doc.createElement('span');
        counter.className = 'ui-hint-counter';
        counter.setAttribute('aria-live', 'polite');
        hint.appendChild(counter);
        const update = () => {
            const length = field.value.length;
            counter.textContent = length + ' / ' + max;
            counter.classList.toggle('is-near-limit', length >= max * 0.9);
        };
        field.addEventListener('input', update);
        update();
    }

    function markRequired(field) {
        if (!field.required || field.type === 'checkbox' || field.type === 'radio') return;
        const label = field.labels && field.labels[0];
        if (label && label.classList.contains('form-label') && !/\*\s*$/.test(label.textContent) && !label.querySelector('.text-danger')) {
            label.classList.add('is-required');
        }
    }

    function enhanceField(field) {
        if (field.__uiField) return;
        field.__uiField = true;
        const type = (field.getAttribute('type') || '').toLowerCase();
        if (field.tagName === 'INPUT' && ['hidden', 'checkbox', 'radio', 'submit', 'button', 'reset', 'image', 'range', 'color'].includes(type)) return;

        markRequired(field);

        const optedOut = field.dataset.uiPlaceholder === 'off' || field.closest('[data-ui-placeholder="off"]');
        if (!optedOut && !field.hasAttribute('placeholder') && !field.readOnly && field.tagName !== 'SELECT' &&
            (field.tagName === 'TEXTAREA' || TEXT_TYPES.includes(type))) {
            const text = placeholderFor(field);
            if (text) field.setAttribute('placeholder', text);
        }

        if (!field.closest('[data-ui-hint="off"]') && !field.closest('table') && !hasExistingHint(field)) {
            const hint = autoHint(field);
            if (hint) {
                const node = doc.createElement('div');
                node.className = 'ui-hint';
                node.id = field.id ? field.id + '-hint' : 'ui-hint-' + (++uid);
                node.innerHTML = '<i class="fas ' + hint.icon + '" aria-hidden="true"></i><span></span>';
                node.lastChild.textContent = hint.text;
                insertAfterAnchor(field, node);
                field.__uiHint = node;
                const described = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
                described.push(node.id);
                field.setAttribute('aria-describedby', described.join(' '));
            }
        }

        addCounter(field);
    }

    /* ---------------------------------------------------------------
     * Wiring
     * ------------------------------------------------------------- */

    function enhance(scope) {
        const base = scope && scope.querySelectorAll ? scope : doc;
        const fields = base.matches && base.matches('input, select, textarea') ? [base] : [];
        fields.push(...base.querySelectorAll('input.form-control, textarea.form-control, select.form-select, select.form-control'));
        fields.forEach(field => {
            if (field.closest('.ui-select-panel, [data-ui="off"]')) return;
            enhanceField(field);
            if (field.tagName === 'SELECT') enhanceSelect(field);
        });
    }

    function clearLoading(button) {
        button.classList.remove('is-loading');
        button.removeAttribute('aria-busy');
        delete button.dataset.uiLoading;
    }

    function boot() {
        enhance(doc);

        new MutationObserver(records => {
            records.forEach(record => record.addedNodes.forEach(node => {
                if (node.nodeType === 1 && !node.closest('.ui-select-panel')) enhance(node);
            }));
        }).observe(doc.body, {childList: true, subtree: true});

        doc.addEventListener('mousedown', event => {
            if (openInstance && !openInstance.panel.contains(event.target) && !openInstance.wrapper.contains(event.target)) {
                openInstance.close(false);
            }
        }, true);
        root.addEventListener('resize', () => openInstance && openInstance.position());
        doc.addEventListener('scroll', event => {
            if (openInstance && !openInstance.panel.contains(event.target)) openInstance.position();
        }, true);
        doc.addEventListener('hide.bs.modal', () => openInstance && openInstance.close(false));

        // Submit buttons show progress once the browser accepts the form.
        doc.addEventListener('submit', event => {
            const form = event.target;
            if (event.defaultPrevented || form.target === '_blank' || form.method.toLowerCase() === 'get') return;
            const button = event.submitter;
            if (button && button.classList.contains('btn') && !button.dataset.uiLoading && button.dataset.uiLoading !== 'off') {
                root.setTimeout(() => {
                    if (event.defaultPrevented) return;
                    button.dataset.uiLoading = '1';
                    button.classList.add('is-loading');
                    button.setAttribute('aria-busy', 'true');
                    // Downloads never navigate away, so release the button eventually.
                    root.setTimeout(() => clearLoading(button), 8000);
                }, 0);
            }
        });
        root.addEventListener('pageshow', () => doc.querySelectorAll('.btn.is-loading').forEach(clearLoading));
    }

    root.YpaUi = {enhance, enhanceSelect, refresh: select => enhanced.get(select) && enhanced.get(select).sync()};

    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', boot);
    else boot();
})(window);
