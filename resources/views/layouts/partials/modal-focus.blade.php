{{-- Capture pointer openers before inline handlers: browsers do not always focus clicked buttons. --}}
<script>
(() => {
    const openers = new WeakMap();
    const visible = [];
    let clicked = null;
    document.addEventListener('click', event => {
        clicked = event.target instanceof Element
            ? event.target.closest('button, a, [role="button"], [data-bs-toggle="modal"], [onclick]') : null;
        const current = clicked;
        queueMicrotask(() => { if (clicked === current) clicked = null; });
    }, true);
    document.addEventListener('show.bs.modal', event => {
        const modal = event.target;
        if (!(modal instanceof Element) || !modal.classList.contains('modal')) return;
        const trigger = event.relatedTarget || clicked || document.activeElement;
        if (trigger instanceof HTMLElement && trigger !== document.body && !modal.contains(trigger)) {
            openers.set(modal, trigger);
        } else {
            openers.delete(modal);
        }
        if (!visible.includes(modal)) visible.push(modal);
        queueMicrotask(() => {
            if (event.defaultPrevented) {
                const index = visible.indexOf(modal);
                if (index !== -1) visible.splice(index, 1);
                openers.delete(modal);
            }
        });
    }, true);
    document.addEventListener('hidden.bs.modal', event => {
        const modal = event.target;
        const trigger = openers.get(modal);
        openers.delete(modal);
        const index = visible.indexOf(modal);
        if (index !== -1) visible.splice(index, 1);
        // Run after Bootstrap's own trigger restoration, without stealing focus
        // from another visible dialog or focusing a control in a hidden parent.
        queueMicrotask(() => {
            const top = [...visible].reverse().find(element => element.isConnected && element.classList.contains('show'));
            const parent = trigger?.closest('.modal');
            const usable = trigger?.isConnected && !trigger.disabled && trigger.getClientRects().length &&
                (!parent || parent.classList.contains('show')) && (!top || top.contains(trigger));
            if (usable) trigger.focus();
            else if (top && !top.contains(document.activeElement)) top.focus();
        });
    }, true);
})();
</script>
