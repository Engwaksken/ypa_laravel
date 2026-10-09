@php
    $itemRows = old('items');
    if (!is_array($itemRows) || $itemRows === []) {
        $itemRows = isset($contract) && $contract?->relationLoaded('items')
            ? $contract->items->map(function ($item) {
                return [
                    'item_name' => $item->item_name,
                    'item_type' => $item->item_type,
                    'quantity' => $item->quantity,
                    'unit_name' => $item->unit_name,
                    'unit_price' => $item->unit_price,
                    'monthly_return' => $item->monthly_return,
                    'total_hives' => $item->total_hives,
                ];
            })->toArray()
            : [[]];
    }
@endphp

<div class="dash-panel mt-4 mb-0">
    <div class="dash-panel-head">
        <span><i class="fas fa-boxes"></i> Contract Items</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-contract-item"><i class="fas fa-plus"></i> Add Item</button>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="contract-items-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Unit Name</th>
                        <th>Unit Price</th>
                        <th>Monthly Return</th>
                        <th>Total Hives</th>
                        <th class="text-end" style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itemRows as $index => $item)
                        <tr>
                            <td><input type="text" class="form-control form-control-sm" name="items[{{ $index }}][item_name]" value="{{ old('items.' . $index . '.item_name', $item['item_name'] ?? '') }}"></td>
                            <td><input type="text" class="form-control form-control-sm" name="items[{{ $index }}][item_type]" value="{{ old('items.' . $index . '.item_type', $item['item_type'] ?? '') }}"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm" name="items[{{ $index }}][quantity]" value="{{ old('items.' . $index . '.quantity', $item['quantity'] ?? '') }}"></td>
                            <td><input type="text" class="form-control form-control-sm" name="items[{{ $index }}][unit_name]" value="{{ old('items.' . $index . '.unit_name', $item['unit_name'] ?? '') }}"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm" name="items[{{ $index }}][unit_price]" value="{{ old('items.' . $index . '.unit_price', $item['unit_price'] ?? '') }}"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm" name="items[{{ $index }}][monthly_return]" value="{{ old('items.' . $index . '.monthly_return', $item['monthly_return'] ?? '') }}"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm" name="items[{{ $index }}][total_hives]" value="{{ old('items.' . $index . '.total_hives', $item['total_hives'] ?? '') }}"></td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-contract-item" title="Remove">&times;</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<template id="contract-item-row-template">
    <tr>
        <td><input type="text" class="form-control form-control-sm" name="items[__INDEX__][item_name]"></td>
        <td><input type="text" class="form-control form-control-sm" name="items[__INDEX__][item_type]"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" name="items[__INDEX__][quantity]"></td>
        <td><input type="text" class="form-control form-control-sm" name="items[__INDEX__][unit_name]"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" name="items[__INDEX__][unit_price]"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" name="items[__INDEX__][monthly_return]"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" name="items[__INDEX__][total_hives]"></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-contract-item" title="Remove">&times;</button></td>
    </tr>
</template>

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    if (event.target && event.target.id === 'add-contract-item') {
        const tbody = document.querySelector('#contract-items-table tbody');
        const index = tbody.querySelectorAll('tr').length;
        const template = document.querySelector('#contract-item-row-template').innerHTML.replaceAll('__INDEX__', index);
        tbody.insertAdjacentHTML('beforeend', template);
    }

    if (event.target && event.target.classList.contains('remove-contract-item')) {
        const row = event.target.closest('tr');
        const tbody = row.closest('tbody');
        if (tbody.querySelectorAll('tr').length > 1) {
            row.remove();
        }
    }
});
</script>
@endpush
