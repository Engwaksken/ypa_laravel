<link rel="icon" href="{{ asset($siteFavicon ?: 'images/favicon.png') }}" data-branding-favicon data-fallback="{{ asset('images/favicon.png') }}">
<link rel="shortcut icon" href="{{ asset($siteFavicon ?: 'images/favicon.png') }}" data-branding-favicon>
<link rel="apple-touch-icon" href="{{ asset($siteFavicon ?: 'images/favicon.png') }}" data-branding-favicon>
<script>
(function () {
    const links = document.querySelectorAll('[data-branding-favicon]');
    const primary = links[0];
    if (!primary || primary.href === primary.dataset.fallback) return;

    // Icon link error events are not reliable across browsers; probe the image.
    const probe = new Image();
    probe.onerror = function () {
        probe.onerror = null;
        links.forEach(function (link) {
            link.href = primary.dataset.fallback;
        });
    };
    probe.src = primary.href;
})();
</script>
