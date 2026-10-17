@extends('layouts.app')

@section('title', 'Notifications')

@php
    $typeIcons = [
        'payment' => ['fa-money-bill-wave', 'success'], 'contract' => ['fa-file-signature', 'primary'],
        'harvest' => ['fa-seedling', 'success'], 'stock' => ['fa-boxes-stacked', 'warning'],
        'meeting' => ['fa-calendar-days', 'info'], 'member' => ['fa-user', 'primary'],
        'warning' => ['fa-triangle-exclamation', 'warning'], 'error' => ['fa-circle-exclamation', 'danger'],
        'success' => ['fa-circle-check', 'success'], 'system' => ['fa-gear', 'secondary'],
    ];
    $iconFor = fn ($type) => $typeIcons[strtolower((string) $type)] ?? ['fa-bell', 'primary'];
    $brandLogo = app(\App\Services\BrandingService::class)->logoUrl();
@endphp

@section('content')
<div class="dash-wrap ypa-notify">

    <section class="ypa-notify-hero">
        <img src="{{ $brandLogo }}" alt="{{ $siteName }} logo" class="ypa-notify-logo" onerror="this.remove()">
        <div class="ypa-notify-hero-text">
            <span class="ypa-notify-eyebrow">{{ $siteName }}</span>
            <h1 class="dash-name mb-0">Notifications</h1>
            <p class="mb-0">Updates about contracts, payments and activity across your branches.</p>
        </div>
        <div class="ypa-notify-stats">
            <div><strong>{{ number_format($stats['unread']) }}</strong><span>Unread</span></div>
            <div><strong>{{ number_format($stats['total']) }}</strong><span>Total</span></div>
        </div>
        @if($stats['unread'] > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" class="ypa-notify-hero-action">
                @csrf
                <button type="submit" class="btn btn-light"><i class="fas fa-check-double" aria-hidden="true"></i> Mark all as read</button>
            </form>
        @endif
    </section>

    <div class="dash-panel">
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('notifications.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="notify-search">Search</label>
                    <input type="search" id="notify-search" name="search" value="{{ $search }}" class="form-control" placeholder="Search by title or message..." data-hint="Matches words in the notification title or message.">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="notify-type">Type</label>
                    <select id="notify-type" name="type" class="form-select">
                        <option value="">All types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected($typeFilter === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="notify-read">Status</label>
                    <select id="notify-read" name="read" class="form-select">
                        <option value="">All statuses</option>
                        <option value="unread" @selected($readFilter === 'unread')>Unread</option>
                        <option value="read" @selected($readFilter === 'read')>Read</option>
                    </select>
                </div>
                <div class="col-12 col-lg-2 form-actions justify-content-lg-start">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter" aria-hidden="true"></i> Filter</button>
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-bell"></i> Inbox</span>
            <span class="text-muted small">{{ number_format($notifications->total()) }} {{ \Illuminate\Support\Str::plural('result', $notifications->total()) }}</span>
        </div>
        <ul class="ypa-notify-list">
            @forelse($notifications as $notification)
                @php([$icon, $tone] = $iconFor($notification->type))
                <li class="ypa-notify-item {{ $notification->is_read ? '' : 'is-unread' }}">
                    <span class="ypa-notify-icon text-bg-{{ $tone }}"><i class="fas {{ $icon }}" aria-hidden="true"></i></span>
                    <div class="ypa-notify-body">
                        <div class="ypa-notify-title">
                            <strong>{{ $notification->title ?: 'Notification' }}</strong>
                            @unless($notification->is_read)<span class="badge rounded-pill text-bg-primary">New</span>@endunless
                        </div>
                        <p>{{ \Illuminate\Support\Str::limit($notification->message ?? '', 220) }}</p>
                        <div class="ypa-notify-meta">
                            <span><i class="fas fa-tag" aria-hidden="true"></i> {{ ucfirst($notification->type ?? 'general') }}</span>
                            @if($notification->created_at)
                                <span title="{{ $notification->created_at->format('j M Y, H:i') }}"><i class="far fa-clock" aria-hidden="true"></i> {{ $notification->created_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                    @if($notification->user_id === auth()->id())
                        <div class="ypa-notify-actions">
                            @unless($notification->is_read)
                                <form action="{{ route('notifications.read', $notification) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success btn-icon" title="Mark as read" aria-label="Mark notification '{{ $notification->title ?: 'untitled' }}' as read"><i class="fas fa-check" aria-hidden="true"></i></button>
                                </form>
                            @endunless
                            <form action="{{ route('notifications.destroy', $notification) }}" method="POST" class="ypa-confirm-delete" data-confirm-title="Delete notification?" data-confirm-message="Delete this notification? This cannot be undone.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete" aria-label="Delete notification '{{ $notification->title ?: 'untitled' }}'"><i class="fas fa-trash" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    @endif
                </li>
            @empty
                <li class="ypa-notify-empty">
                    <i class="far fa-bell-slash" aria-hidden="true"></i>
                    <strong>You're all caught up</strong>
                    <span>No notifications match these filters.</span>
                </li>
            @endforelse
        </ul>
    </div>

    {{ $notifications->links() }}

</div>
@endsection

@push('styles')
<style>
.ypa-notify-hero{position:relative;display:flex;flex-wrap:wrap;align-items:center;gap:18px;margin-bottom:20px;padding:22px 24px;overflow:hidden;color:#fff;text-align:left;background:linear-gradient(120deg,var(--ds-brand-dark),var(--ds-brand) 60%,var(--ds-brand-light));border-radius:var(--ds-radius);box-shadow:var(--ds-shadow)}
.ypa-notify-hero::after{content:"";position:absolute;right:-40px;bottom:-60px;width:220px;height:220px;background:var(--notify-logo) center/contain no-repeat;opacity:.08;pointer-events:none}
.ypa-notify-logo{width:58px;height:58px;padding:8px;object-fit:contain;background:#fff;border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,.15)}
.ypa-notify-hero-text{flex:1 1 260px;min-width:0}.ypa-notify-hero .dash-name{color:#fff}
.ypa-notify-hero p{opacity:.85;font-size:.9rem}.ypa-notify-eyebrow{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.8}
.ypa-notify-stats{display:flex;gap:10px}.ypa-notify-stats div{display:grid;min-width:84px;padding:8px 14px;text-align:center;background:rgba(255,255,255,.14);border-radius:12px}
.ypa-notify-stats strong{font-size:1.35rem;line-height:1.2}.ypa-notify-stats span{font-size:.72rem;opacity:.85}
.ypa-notify-hero-action{position:relative;z-index:1}
.ypa-notify-list{margin:0;padding:0;list-style:none}
.ypa-notify-item{display:flex;align-items:flex-start;gap:14px;padding:16px 18px;text-align:left;border-bottom:1px solid var(--ds-border);transition:background-color .12s}
.ypa-notify-item:last-child{border-bottom:0}.ypa-notify-item:hover{background:#f8fafc}
.ypa-notify-item.is-unread{background:#f3f8fd;box-shadow:inset 3px 0 0 var(--ds-brand)}
.ypa-notify-icon{flex:0 0 auto;display:grid;place-items:center;width:40px;height:40px;border-radius:12px;font-size:.95rem}
.ypa-notify-body{flex:1 1 auto;min-width:0}.ypa-notify-title{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.ypa-notify-body p{margin:4px 0 6px;color:#334155;font-size:.875rem;overflow-wrap:anywhere}
.ypa-notify-meta{display:flex;flex-wrap:wrap;gap:14px;font-size:.75rem;color:var(--ds-text-muted)}.ypa-notify-meta i{margin-right:3px}
.ypa-notify-actions{display:flex;gap:6px;flex:0 0 auto}
.ypa-notify-empty{display:grid;justify-items:center;gap:4px;padding:48px 16px;color:var(--ds-text-muted)}.ypa-notify-empty i{font-size:2rem;opacity:.45}
@media (max-width:575.98px){.ypa-notify-hero{padding:18px}.ypa-notify-stats{width:100%}.ypa-notify-stats div{flex:1}.ypa-notify-hero-action,.ypa-notify-hero-action .btn{width:100%}.ypa-notify-item{flex-wrap:wrap;padding:14px}.ypa-notify-actions{width:100%;justify-content:flex-end}}
</style>
<style>.ypa-notify-hero{--notify-logo:url("{{ $brandLogo }}")}</style>
@endpush
