<div class="top-nav">
    <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>

    <div class="user-section">
        @php
            try {
                $showBell = Route::has('notifications.index') && app(\App\Services\PermissionService::class)->can('notifications');
                $unreadCount = $showBell ? \App\Models\Notification::forUser(auth()->id())->unread()->count() : 0;
            } catch (\Throwable) {
                $showBell = false;
                $unreadCount = 0;
            }
        @endphp
        @if($showBell)
            <a href="{{ route('notifications.index') }}" class="topnav-bell" aria-label="Notifications{{ $unreadCount ? ', ' . $unreadCount . ' unread' : '' }}" title="Notifications">
                <i class="fas fa-bell" aria-hidden="true"></i>
                @if($unreadCount)<span class="topnav-bell-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
            </a>
        @endif
        <span class="user-name">Hello, {{ auth()->user()->name ?? 'User' }}</span>
        @if(Route::has('member.profile'))
            <a href="{{ route('member.profile') }}" class="logout-link"><i class="fa fa-user"></i> Profile</a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="inline-form">
            @csrf
            <button type="submit" class="logout-link">
                <i class="fa fa-right-from-bracket"></i> Logout
            </button>
        </form>
    </div>
</div>
