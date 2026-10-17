{{-- Shared behaviour for tabbed forms (modal + full-page). --}}
<script>
window.YpaFormTabs = (function () {
    function triggerFor(form, pane) {
        if (!pane || !pane.id) return null;
        return form.querySelector('[data-bs-target="#' + pane.id + '"]');
    }

    function activate(form, pane) {
        const trigger = triggerFor(form, pane);
        if (!trigger) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(trigger).show();
        } else {
            trigger.click();
        }
    }

    // Move to the tab holding the first invalid field, otherwise the first tab.
    function showErrorTab(form) {
        if (!form) return;
        const invalid = form.querySelector('.is-invalid');
        const pane = (invalid && invalid.closest('.tab-pane')) || form.querySelector('.tab-pane');
        activate(form, pane);
    }

    function bind(form) {
        if (!form || form.dataset.ypaTabsBound) return;
        form.dataset.ypaTabsBound = '1';
        form.addEventListener('invalid', function (event) {
            const pane = event.target && event.target.closest ? event.target.closest('.tab-pane') : null;
            if (!pane) return;
            activate(form, pane);
        }, true);
    }

    return { bind: bind, showErrorTab: showErrorTab };
})();

// Bind every tabbed form once the page is ready (modals and full-page forms alike).
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        if (form.querySelector('.nav-tabs')) window.YpaFormTabs.bind(form);
    });
});
</script>
