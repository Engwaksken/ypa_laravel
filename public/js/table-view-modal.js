(function (root) {
    'use strict';
    if (!root.document) return;
    const document = root.document;
    const main = document.querySelector('main.main-content');
    const modal = document.getElementById('tableViewModal');
    const body = document.getElementById('tableViewModalBody');
    const title = document.getElementById('tableViewModalTitle');
    const fullPage = document.getElementById('tableViewFullPage');
    const download = document.getElementById('tableViewPdfDownload');
    const notice = document.getElementById('tableViewModalNotice');
    if (!main || !modal || !body || !title || !fullPage || !root.bootstrap?.Modal) return;
    let controller = null;
    let revision = 0;
    let objectUrl = null;

    function releasePdf() {
        if (objectUrl) root.URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        if (download) { download.hidden = true; download.removeAttribute('href'); }
    }

    function displayPdf(blob, label, printWhenReady = false) {
        releasePdf();
        objectUrl = root.URL.createObjectURL(blob);
        const frame = document.createElement('iframe');
        frame.className = 'platform-pdf-frame';
        frame.title = label;
        frame.src = objectUrl;
        if (printWhenReady) {
            frame.addEventListener('load', () => {
                // Wait for the PDF viewer to finish initializing and the modal
                // to become visible before invoking the browser print dialog.
                root.setTimeout(() => {
                    try {
                        frame.contentWindow?.focus();
                        frame.contentWindow?.print();
                    } catch (_) {
                        if (notice) notice.textContent = 'The PDF is ready. Use the browser print command if the print dialog did not open.';
                    }
                }, 500);
            }, {once: true});
        }
        body.replaceChildren(frame);
        if (download) {
            download.href = objectUrl;
            download.download = (label.replace(/[^a-z0-9_-]+/gi, '-').slice(0, 100) || 'document') + '.pdf';
            download.hidden = false;
        }
        if (notice) notice.textContent = printWhenReady
            ? 'Opening the print dialog. Use Download PDF to save a copy.'
            : 'PDF preview. Use Download PDF if your browser does not display embedded documents.';
    }

    root.YpaDocumentViewer = {
        showBlob(blob, label, trigger) {
            controller?.abort();
            ++revision;
            title.textContent = label;
            fullPage.hidden = true;
            body.setAttribute('aria-busy', 'false');
            displayPdf(blob, label, true);
            root.bootstrap.Modal.getOrCreateInstance(modal).show(trigger);
        }
    };

    function status(message, error = false) {
        const notice = document.createElement('div');
        notice.className = error ? 'alert alert-warning mb-0' : 'text-muted py-4 text-center';
        notice.setAttribute('role', error ? 'alert' : 'status');
        notice.textContent = message;
        body.replaceChildren(notice);
    }

    function readOnlyContent(html) {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const content = parsed.querySelector('main.main-content');
        if (!content) throw new Error('The detail preview is unavailable. Please open the full page.');
        content.querySelectorAll('script, style, link, meta, base, iframe, object, embed, svg, math, video, audio, form, button, input, select, textarea, .modal, .ypa-table-tools, .screen-only, .dash-head > .d-flex, .alert-dismissible').forEach(node => node.remove());
        content.querySelectorAll('a').forEach(link => {
            const label = parsed.createElement('span');
            label.append(...Array.from(link.childNodes));
            link.replaceWith(label);
        });
        const allowed = new Set(['class', 'colspan', 'rowspan', 'scope', 'alt', 'title', 'aria-label']);
        content.querySelectorAll('*').forEach(node => {
            for (const attribute of Array.from(node.attributes)) {
                if (node.tagName === 'IMG' && attribute.name === 'src') {
                    try {
                        const url = new URL(attribute.value, root.location.href);
                        if (url.origin === root.location.origin && /^https?:$/.test(url.protocol)) {
                            node.setAttribute('src', url.href);
                            continue;
                        }
                    } catch (_) { /* Drop malformed image URLs. */ }
                }
                // Remove IDs, handlers, data hooks and styles to prevent collisions or page actions.
                if (!allowed.has(attribute.name)) node.removeAttribute(attribute.name);
            }
        });
        return Array.from(content.childNodes);
    }

    async function show(link, url) {
        controller?.abort();
        releasePdf();
        const current = ++revision;
        controller = new AbortController();
        const signal = controller.signal;
        title.textContent = link.getAttribute('aria-label') || link.getAttribute('title') || 'Record details';
        fullPage.href = url.href;
        fullPage.hidden = false;
        if (notice) notice.textContent = 'Read-only preview. Open the full page for actions and attachments.';
        body.setAttribute('aria-busy', 'true');
        status('Loading details…');
        root.bootstrap.Modal.getOrCreateInstance(modal).show(link);
        try {
            const resource = new URL(link.getAttribute('data-document-url') || url.href, root.location.href);
            if (resource.origin !== root.location.origin || !/^https?:$/.test(resource.protocol)) throw new Error('This document cannot be previewed.');
            const response = await root.fetch(resource.href, {signal, credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'text/html, application/pdf'}});
            if (response.redirected) throw new Error('Your session may have expired. Open the full page to continue.');
            if (!response.ok) throw new Error(response.status === 403 ? 'You do not have permission to view this record.' :
                response.status === 404 ? 'This record could not be found.' : 'Unable to load details. Please open the full page or try again.');
            const contentType = response.headers.get('Content-Type') || '';
            if (contentType.includes('application/pdf')) {
                const blob = await response.blob();
                if (current === revision && !signal.aborted) displayPdf(blob, title.textContent);
                return;
            }
            if (!contentType.includes('text/html')) {
                throw new Error('This page does not support a detail preview. Please open the full page.');
            }
            const html = await response.text();
            if (current !== revision || signal.aborted) return;
            body.replaceChildren(...readOnlyContent(html));
        } catch (error) {
            if (current === revision && !signal.aborted) status(error.message || 'Unable to load details.', true);
        } finally {
            if (current === revision) body.setAttribute('aria-busy', 'false');
        }
    }

    // Delegation also handles links in tables loaded after the initial page render.
    main.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest?.('a[href]');
        const pdfLink = link && (link.hasAttribute('data-pdf-preview') || /(?:\.pdf|\/pdf)(?:[?#]|$)/i.test(link.href));
        if (!link || (!pdfLink && !link.closest('table')) || link.closest('.modal') || link.hasAttribute('download') ||
            link.hasAttribute('data-bs-toggle') || link.hasAttribute('onclick') || (link.target && link.target !== '_self')) return;
        const label = link.getAttribute('aria-label') || link.getAttribute('title') || link.textContent.trim();
        if (!pdfLink && !/^view\b/i.test(label) && !link.querySelector('.fa-eye')) return;
        let url;
        try { url = new URL(link.href, root.location.href); } catch (_) { return; }
        if (url.origin !== root.location.origin || !/^https?:$/.test(url.protocol) || url.hash) return;
        event.preventDefault();
        show(link, url);
    });

    modal.addEventListener('hide.bs.modal', () => {
        ++revision;
        controller?.abort();
        controller = null;
        body.replaceChildren();
        releasePdf();
        body.setAttribute('aria-busy', 'false');
    });
})(typeof window !== 'undefined' ? window : globalThis);
