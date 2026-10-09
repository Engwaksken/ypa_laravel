<div class="dash-panel">
    <div class="dash-panel-head">
        <span><i class="fas fa-truck"></i> Suppliers</span>
        <span class="text-muted small">{{ number_format($sectionData['total'] ?? 0) }} supplier(s)</span>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Contact</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-end">Estimated Purchases</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sectionData['rows'] ?? [] as $row)
                        <tr>
                            <td class="fw-bold">{{ $row['supplier']->name }}</td>
                            <td>{{ $row['supplier']->contact_name ?: '-' }}</td>
                            <td>{{ $row['supplier']->phone ?: '-' }}</td>
                            <td>{{ $row['supplier']->email ?: '-' }}</td>
                            <td class="text-end fw-bold">{{ $money($row['spend']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>