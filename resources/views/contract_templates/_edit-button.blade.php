{{-- Edit button that opens the contract template modal pre-filled. Needs $template, $buttonClass, $label. --}}
<button type="button" class="{{ $buttonClass }}" title="Edit" aria-label="Edit template {{ $template->template_name }}"
    data-url="{{ route('contract-templates.update', $template) }}"
    data-name="{{ $template->template_name }}"
    data-key="{{ $template->template_key ?? '' }}"
    data-project-type="{{ $template->project_type_id ?? '' }}"
    data-project-category="{{ $template->project_category_id ?? '' }}"
    data-version="{{ $template->version ?? 1 }}"
    data-active="{{ $template->is_active ? '1' : '0' }}"
    data-cover="{{ $template->cover_page ?? '' }}"
    data-body="{{ $template->template_body ?? '' }}"
    data-footer="{{ $template->contract_footer ?? '' }}"
    data-signature="{{ $template->contract_signature ?? '' }}"
    data-sections="{{ is_array($template->template_sections) ? json_encode($template->template_sections, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}"
    onclick="openContractTemplateEdit(this)">
    <i class="fas fa-pen{{ $label !== '' ? ' me-1' : '' }}" aria-hidden="true"></i>{{ $label }}
</button>
