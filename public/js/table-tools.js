(function (root) {
    'use strict';

    function clean(value) {
        return String(value == null ? '' : value).replace(/\s+/g, ' ').trim();
    }

    function csvCell(value) {
        let text = clean(value);
        // Neutralise spreadsheet formulas, including those preceded by whitespace.
        if (/^[=+\-@]/.test(text)) text = "'" + text;
        return '"' + text.replace(/"/g, '""') + '"';
    }

    function csv(headers, rows) {
        return '\uFEFF' + [headers, ...rows].map(row => row.map(csvCell).join(',')).join('\r\n') + '\r\n';
    }

    // CommonJS exposes pure helpers for tests; no browser dependency is needed.
    if (typeof module !== 'undefined' && module.exports) module.exports = {csv, csvCell};
    if (!root.document) return;
    const document = root.document;
    const main = document.querySelector('main.main-content');
    if (!main) return;
    const initialized = new WeakSet();
    const originalSpans = new WeakMap();
    let sequence = 0;

    function text(cell) {
        if (cell.dataset.exportValue !== undefined) return cell.dataset.exportValue;
        const clone = cell.cloneNode(true);
        clone.querySelectorAll('button, input, select, textarea, script, style, .visually-hidden, [data-export-ignore]').forEach(node => node.remove());
        return clean(clone.textContent);
    }

    function span(cell) {
        if (!originalSpans.has(cell)) originalSpans.set(cell, cell.colSpan || 1);
        return originalSpans.get(cell);
    }

    function enhance(table) {
        if (initialized.has(table) ||
            table.closest('form, .modal, [data-table-tools="off"], .contract-document') || table.parentElement.closest('table')) return;
        if (!table.tHead) {
            const rows = Array.from(table.tBodies).flatMap(tbody => Array.from(tbody.rows));
            const width = rows[0]?.cells.length || 0;
            if (width < 2 || rows.some(row => row.cells.length !== width ||
                Array.from(row.cells).some(cell => cell.colSpan !== 1 || cell.rowSpan !== 1))) return;
            const head = document.createElement('thead');
            const row = document.createElement('tr');
            for (let index = 0; index < width; index++) {
                const cell = document.createElement('th');
                cell.textContent = width === 2 ? ['Field', 'Value'][index] : 'Column ' + (index + 1);
                cell.setAttribute('scope', 'col');
                row.append(cell);
            }
            head.append(row);
            table.tBodies[0].before(head);
        }
        if (table.tHead.rows.length !== 1) return;
        const headings = Array.from(table.tHead.rows[0].cells);
        if (!headings.length || headings.some(cell => cell.colSpan > 1 || cell.rowSpan > 1)) return;
        initialized.add(table);
        const id = 'ypa-table-' + (++sequence);
        const panel = table.closest('.dash-panel');
        const title = clean(table.caption?.textContent || panel?.querySelector('.dash-panel-head span')?.textContent ||
            document.querySelector('h1')?.textContent || document.title);
        const labels = headings.map((cell, index) => clean(cell.textContent) || 'Column ' + (index + 1));
        const exportable = headings.map((cell, index) => cell.dataset.export !== 'false' && !/^(actions?|select)$/i.test(labels[index]) &&
            !cell.querySelector('input, button'));
        const selected = headings.map(() => true);
        const section = document.createElement('section');
        section.className = 'ypa-table-section';
        section.setAttribute('aria-label', title + ' table controls');
        table.before(section);
        const toolbar = document.createElement('div');
        toolbar.className = 'ypa-table-tools';
        const body = document.createElement('div');
        body.id = id;
        body.className = 'ypa-table-body';
        section.append(toolbar, body);
        body.append(table);

        function button(label, handler) {
            const node = document.createElement('button');
            node.type = 'button';
            node.className = 'btn btn-sm btn-outline-secondary';
            node.textContent = label;
            node.addEventListener('click', handler);
            toolbar.append(node);
            return node;
        }

        const collapse = button('Collapse table', () => {
            body.hidden = !body.hidden;
            collapse.textContent = body.hidden ? 'Expand table' : 'Collapse table';
            collapse.setAttribute('aria-expanded', String(!body.hidden));
        });
        collapse.setAttribute('aria-controls', id);
        collapse.setAttribute('aria-expanded', 'true');
        const details = document.createElement('details');
        details.className = 'ypa-table-columns';
        const summary = document.createElement('summary');
        summary.textContent = 'Columns';
        details.append(summary);
        const choices = document.createElement('div');
        choices.className = 'ypa-table-column-choices';
        details.append(choices);
        toolbar.append(details);
        const inputs = [];
        let pdfInFlight = false;
        const notice = document.createElement('small');
        notice.className = 'text-muted';
        notice.setAttribute('role', 'status');
        toolbar.append(notice);

        function update() {
            const count = selected.filter(Boolean).length;
            Array.from(table.rows).forEach(row => {
                let column = 0;
                Array.from(row.cells).forEach(cell => {
                    const width = span(cell);
                    const visible = selected.slice(column, column + width).filter(Boolean).length;
                    cell.classList.toggle('ypa-table-column-hidden', visible === 0);
                    cell.colSpan = Math.max(1, visible);
                    column += width;
                });
            });
            inputs.forEach((input, index) => {
                input.checked = selected[index];
                input.disabled = selected[index] && count === 1;
            });
            const canExport = selected.some((value, index) => value && exportable[index]);
            exportButtons.forEach((node, index) => { node.disabled = !canExport || (index === 1 && pdfInFlight); });
            notice.textContent = 'Exports include selected data columns and loaded rows only.';
        }

        labels.forEach((label, index) => {
            const wrapper = document.createElement('label');
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.checked = true;
            input.addEventListener('change', () => { selected[index] = input.checked; update(); });
            wrapper.append(input, document.createTextNode(' ' + label + (exportable[index] ? '' : ' (not exported)')));
            choices.append(wrapper);
            inputs.push(input);
        });
        button('Show all columns', () => { selected.fill(true); update(); });

        function snapshot() {
            const columns = selected.map((value, index) => value && exportable[index] ? index : -1).filter(index => index >= 0);
            const rows = Array.from(table.tBodies).flatMap(tbody => Array.from(tbody.rows))
                .filter(row => !row.hidden && !row.classList.contains('empty-row') &&
                    root.getComputedStyle(row).display !== 'none' && row.cells.length === headings.length &&
                    Array.from(row.cells).every(cell => span(cell) === 1 && cell.rowSpan === 1))
                .map(row => columns.map(index => text(row.cells[index])));
            return {headers: columns.map(index => labels[index]), rows};
        }

        const exportButtons = [];
        if (main.dataset.tableExport === 'true') {
            exportButtons.push(button('CSV', () => {
                const data = snapshot();
                const blob = new Blob([csv(data.headers, data.rows)], {type: 'text/csv;charset=utf-8'});
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = (title.replace(/[^a-z0-9_-]+/gi, '-').replace(/^-|-$/g, '') || 'table') + '.csv';
                link.click();
                root.setTimeout(() => URL.revokeObjectURL(url), 1000);
            }));
            exportButtons.push(button('PDF', async () => {
                if (pdfInFlight) return;
                pdfInFlight = true;
                const data = snapshot();
                const pdfButton = exportButtons[1];
                pdfButton.disabled = true;
                notice.textContent = 'Generating PDF…';
                try {
                    if (!root.YpaDocumentViewer || !main.dataset.tablePdfUrl) throw new Error('The document viewer is unavailable. Reload this page.');
                    const response = await root.fetch(main.dataset.tablePdfUrl, {
                        method: 'POST', credentials: 'same-origin', cache: 'no-store',
                        headers: {'Content-Type': 'application/json', Accept: 'application/pdf, application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                        body: JSON.stringify({title, headers: data.headers, rows: data.rows})
                    });
                    if (!response.ok || !(response.headers.get('Content-Type') || '').includes('application/pdf')) {
                        throw new Error('PDF generation failed. Check your session or filter the table to at most 500 rows and 30 columns.');
                    }
                    root.YpaDocumentViewer.showBlob(await response.blob(), title, pdfButton);
                    notice.textContent = 'PDF opened in the platform document viewer.';
                } catch (error) {
                    notice.textContent = error.message || 'Unable to generate the PDF.';
                } finally {
                    pdfInFlight = false;
                    pdfButton.disabled = !selected.some((value, index) => value && exportable[index]);
                }
            }));
        }
        update();
        new MutationObserver(update).observe(table, {childList: true, subtree: true});
    }

    function scan() { main.querySelectorAll('table').forEach(enhance); }
    scan();
    new MutationObserver(scan).observe(main, {childList: true, subtree: true});
})(typeof window !== 'undefined' ? window : globalThis);
