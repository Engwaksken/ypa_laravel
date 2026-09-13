<div class="top-nav">
    <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>

    <div class="user-section">
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
