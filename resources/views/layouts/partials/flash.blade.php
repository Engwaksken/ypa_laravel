{{-- Global success / error popups (custom YPA toasts + dismissible alerts).
     Renders session flash data set by controllers: success, error, warning, info. --}}
@php
    $flashMessages = collect([
        ['key' => 'success', 'type' => 'success', 'icon' => 'fas fa-circle-check'],
        ['key' => 'error', 'type' => 'danger', 'icon' => 'fas fa-circle-exclamation'],
        ['key' => 'warning', 'type' => 'warning', 'icon' => 'fas fa-triangle-exclamation'],
        ['key' => 'info', 'type' => 'info', 'icon' => 'fas fa-circle-info'],
    ])->filter(fn (array $flash) => session()->has($flash['key']));
@endphp

@if($flashMessages->isNotEmpty() || $errors->any())
    <div class="ypa-flash-stack" id="ypaFlashStack" role="status" aria-live="polite">
        @foreach($flashMessages as $flash)
            <div class="alert alert-{{ $flash['type'] }} alert-dismissible fade show ypa-flash" role="alert">
                <i class="{{ $flash['icon'] }} me-2"></i>
                <span>{{ session($flash['key']) }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endforeach

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show ypa-flash" role="alert">
                <i class="fas fa-circle-exclamation me-2"></i>
                <span>
                    <strong>Please fix the following:</strong>
                </span>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <script>
        (function () {
            var stack = document.getElementById('ypaFlashStack');
            if (!stack) return;

            stack.querySelectorAll('.ypa-flash').forEach(function (el) {
                setTimeout(function () {
                    if (window.bootstrap && bootstrap.Alert) {
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    } else {
                        el.remove();
                    }
                }, 6000);
            });
        })();
    </script>
@endif
