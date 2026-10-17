@php($appearance = app(\App\Services\AppearanceService::class))
@php($theme = $appearance->values())
<style id="system-theme">
:root {
    --ds-brand: {{ $theme['theme_primary'] }};
    --ds-brand-dark: {{ $theme['theme_primary_dark'] }};
    --ds-brand-light: {{ $theme['theme_accent'] }};
    --ds-bg: {{ $theme['theme_background'] }};
    --ds-surface: {{ $theme['theme_surface'] }};
    --ds-border: {{ $theme['theme_border'] }};
    --ds-text: {{ $theme['theme_text'] }};
    --ds-text-muted: {{ $theme['theme_muted'] }};
    --bs-primary: {{ $theme['theme_primary'] }};
    --bs-primary-rgb: {{ $appearance->rgb('theme_primary') }};
    --bs-success-rgb: {{ $appearance->rgb('theme_success') }};
    --bs-danger-rgb: {{ $appearance->rgb('theme_danger') }};
    --bs-warning-rgb: {{ $appearance->rgb('theme_warning') }};
    --bs-link-color: {{ $theme['theme_primary'] }};
    --bs-link-hover-color: {{ $theme['theme_primary_dark'] }};
    --bs-body-color: {{ $theme['theme_text'] }};
    --bs-body-bg: {{ $theme['theme_background'] }};
    --bs-border-color: {{ $theme['theme_border'] }};
    --ui-danger: {{ $theme['theme_danger'] }};
    --ui-success: {{ $theme['theme_success'] }};
}
body, input, select, textarea, button { font-family: {!! $appearance->font() !!}; }
@media screen {
    html { font-size: {{ $theme['theme_base_size'] }}px; }
    body { font-size: 1rem; background: var(--ds-bg); color: var(--ds-text); }
    h1, .dash-name { font-size: {{ $theme['theme_heading_size'] }}px; }
    table, .table { font-size: {{ $theme['theme_table_size'] }}px; }
}
table, .table { color: var(--ds-text); --bs-table-color: var(--ds-text); --bs-table-bg: var(--ds-surface); }
.sidebar, .sidebar .nav-menu .dropdown { background: {{ $theme['theme_sidebar'] }}; }
.sidebar .brand-link, .sidebar .brand-name, .sidebar .nav-menu li a, .sidebar .nav-menu .dropdown-toggle { color: {{ $theme['theme_sidebar_text'] }}; }
.top-nav { background: {{ $theme['theme_navigation'] }}; color: {{ $theme['theme_navigation_text'] }}; }
.card, .dash-panel, .modal-content { background: var(--ds-surface); color: var(--ds-text); border-color: var(--ds-border); }
.form-label, .form-control, .form-select { color: var(--ds-text); }
.form-control, .form-select { background-color: var(--ds-surface); border-color: var(--ds-border); }
.text-muted { color: var(--ds-text-muted)!important; }
.btn.btn-primary { --bs-btn-bg: var(--ds-brand); --bs-btn-border-color: var(--ds-brand); --bs-btn-hover-bg: var(--ds-brand-dark); --bs-btn-hover-border-color: var(--ds-brand-dark); --bs-btn-active-bg: var(--ds-brand-dark); background-color: var(--ds-brand); border-color: var(--ds-brand); }
.btn-outline-primary { --bs-btn-color: var(--ds-brand); --bs-btn-border-color: var(--ds-brand); --bs-btn-hover-bg: var(--ds-brand); --bs-btn-hover-border-color: var(--ds-brand); }
.btn-success { background-color: {{ $theme['theme_success'] }}; border-color: {{ $theme['theme_success'] }}; }
.btn-danger { background-color: {{ $theme['theme_danger'] }}; border-color: {{ $theme['theme_danger'] }}; }
.btn-warning { background-color: {{ $theme['theme_warning'] }}; border-color: {{ $theme['theme_warning'] }}; }
</style>
