(() => {
    const form = document.getElementById('posForm');
    if (!form) return;
    const quantities = [...form.querySelectorAll('.pos-quantity')];
    const discount = document.getElementById('posDiscount');
    const status = document.getElementById('posPaymentStatus');
    const paid = document.getElementById('posAmountPaid');
    const customer = document.getElementById('posCustomer');
    const submit = document.getElementById('posSubmit');
    const error = document.getElementById('posError');
    let submitting = false;
    const money = value => 'UGX ' + value.toLocaleString('en-UG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    function cart() {
        return quantities.filter(input => Number(input.value) > 0).map(input => ({id: Number(input.dataset.id), quantity: Number(input.value)}));
    }
    function update() {
        const subtotal = quantities.reduce((sum, input) => sum + Math.max(0, Number(input.value) || 0) * Number(input.dataset.price), 0);
        const total = Math.max(0, subtotal - (Number(discount.value) || 0));
        const partial = status.value === 'partially paid';
        document.getElementById('posPartialGroup').hidden = !partial;
        paid.disabled = !partial;
        paid.required = partial;
        paid.max = total.toFixed(2);
        customer.required = status.value !== 'paid';
        discount.max = subtotal.toFixed(2);
        document.getElementById('posSubtotal').textContent = money(subtotal);
        document.getElementById('posTotal').textContent = money(total);
        document.getElementById('posBalance').textContent = money(status.value === 'paid' ? 0 : Math.max(0, total - (partial ? Number(paid.value) || 0 : 0)));
        return total;
    }
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    document.getElementById('posSearch').addEventListener('input', event => {
        const term = event.target.value.trim().toLowerCase();
        form.querySelectorAll('[data-pos-product]').forEach(row => { row.hidden = !row.dataset.search.includes(term); });
    });
    form.addEventListener('submit', event => {
        if (submitting) { event.preventDefault(); return; }
        error.hidden = true;
        const total = update();
        if (!form.checkValidity()) {
            event.preventDefault();
            quantities.forEach(input => { if (!input.checkValidity()) input.closest('tr').hidden = false; });
            form.reportValidity();
            return;
        }
        if (!cart().length) {
            event.preventDefault();
            error.textContent = 'Select at least one product.';
            error.hidden = false;
            return;
        }
        document.getElementById('posItems').value = JSON.stringify(cart());
        submitting = true;
        submit.disabled = true;
        submit.textContent = 'Processing invoice…';
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        submit.disabled = quantities.length === 0;
        submit.textContent = 'Process invoice';
        update();
    });
    update();
})();
