<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ $siteName }}</title>

    <link rel="icon" href="{{ asset($siteFavicon) }}">
    <link rel="shortcut icon" href="{{ asset($siteFavicon) }}">
    <link rel="apple-touch-icon" href="{{ asset($siteFavicon) }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>

@include('layouts.partials.topnav')

<div class="overlay" id="overlay"></div>

@include('layouts.partials.sidebar')

<main class="main-content">
    @yield('content')
</main>

<script>
(function () {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    document.querySelectorAll('.nav-menu .dropdown-toggle').forEach(function (toggle) {
        const dropdown = toggle.closest('.dropdown');
        if (!dropdown) return;

        toggle.addEventListener('click', function () {
            const isOpen = dropdown.classList.contains('open');

            dropdown.classList.toggle('open', !isOpen);
            dropdown.classList.toggle('active', !isOpen);
            toggle.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
        });
    });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
