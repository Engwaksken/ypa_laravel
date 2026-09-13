@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Notifications</h1>
            <div class="dash-date">{{ number_format($stats['unread']) }} unread</div>
        </div>
        <form method="POST" action="{{ route('notifications.read-all') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-check-double me-1"></i> Mark all as read</button>
        </form>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Notifications</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('notifications.index') }}" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Title or message...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected($typeFilter === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="read" class="form-select">
                        <option value="">All</option>
                        <option value="unread" @selected($readFilter === 'unread')>Unread</option>
                        <option value="read" @selected($readFilter === 'read')>Read</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-bell"></i> Notification List</span>
            <span class="text-muted small">{{ $notifications->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                            <tr class="{{ $notification->is_read ? '' : 'table-light' }}">
                                <td><strong>{{ $notification->title }}</strong></td>
                                <td style="max-width:320px;">{{ \Illuminate\Support\Str::limit($notification->message ?? '', 120) }}</td>
                                <td><span class="badge bg-info">{{ ucfirst($notification->type ?? 'other') }}</span></td>
                                <td><span class="badge bg-{{ $notification->is_read ? 'secondary' : 'warning' }}">{{ $notification->is_read ? 'Read' : 'Unread' }}</span></td>
                                <td class="text-end">
                                    @if($notification->user_id === auth()->id() && !$notification->is_read)
                                        <form action="{{ route('notifications.read', $notification) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Mark as read"><i class="fas fa-check"></i></button>
                                        </form>
                                    @endif
                                    @if($notification->user_id === auth()->id())
                                        <form action="{{ route('notifications.destroy', $notification) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete notification?" data-confirm-message="Delete this notification? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No notifications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{ $notifications->links() }}

</div>
@endsection
