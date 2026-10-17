<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AppearanceService
{
    private ?array $fieldDefinitions = null;

    public function fields(): array
    {
        if ($this->fieldDefinitions !== null) return $this->fieldDefinitions;
        $fields = [];
        foreach (config('appearance.colors') as $name => $default) {
            $fields['theme_' . $name] = ['group' => 'Theme', 'label' => ucwords(str_replace('_', ' ', $name)) . ' color', 'type' => 'color', 'default' => $default, 'rules' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']];
        }
        $fields['theme_font'] = ['group' => 'Theme', 'label' => 'Font family', 'type' => 'select', 'options' => ['system' => 'System / Segoe UI', 'arial' => 'Arial', 'serif' => 'Georgia / Times', 'mono' => 'Courier'], 'default' => 'system', 'rules' => ['required', Rule::in(array_keys(config('appearance.fonts')))]];
        foreach (['base' => 15, 'heading' => 28, 'table' => 14] as $name => $default) {
            $fields['theme_' . $name . '_size'] = ['group' => 'Theme', 'label' => ucfirst($name) . ' font size (px)', 'type' => 'number', 'min' => 10, 'max' => 48, 'default' => (string) $default, 'rules' => ['required', 'integer', 'between:10,48']];
        }
        foreach (['invoice' => 'Sales receipt', 'receipt' => 'OFFICIAL PAYMENT RECEIPT', 'contract' => 'Contract Agreement'] as $kind => $title) {
            $group = ucfirst($kind);
            foreach (['title' => $title, 'subtitle' => $kind === 'receipt' ? 'Official payment receipt' : '', 'header' => '', 'footer' => $kind === 'invoice' ? 'Thank you for your purchase.' : '', 'terms' => ''] as $part => $default) {
                if ($kind === 'contract' && $part !== 'title') continue;
                $fields[$kind . '_' . $part] = ['group' => $group, 'label' => ucfirst($part) . ' wording', 'type' => 'textarea', 'default' => $default, 'rules' => ['nullable', 'string', 'max:2000']];
            }
            $papers = ['a4' => 'A4', 'letter' => 'Letter', 'legal' => 'Legal'];
            if ($kind !== 'contract') $papers += ['a5' => 'A5', '80mm' => '80 mm roll', '58mm' => '58 mm roll'];
            $fields[$kind . '_paper'] = ['group' => $group, 'label' => 'Print paper size', 'type' => 'select', 'options' => $papers, 'default' => 'a4', 'rules' => ['required', Rule::in(array_keys($papers))]];
            $fields[$kind . '_font_size'] = ['group' => $group, 'label' => 'Print font size (pt)', 'type' => 'number', 'min' => 8, 'max' => 20, 'default' => '11', 'rules' => ['required', 'integer', 'between:8,20']];
            $fields[$kind . '_margin'] = ['group' => $group, 'label' => 'Print margin (mm)', 'type' => 'number', 'min' => 0, 'max' => 25, 'default' => '12', 'rules' => ['required', 'integer', 'between:0,25']];
        }
        return $this->fieldDefinitions = $fields;
    }

    public function rules(): array
    {
        return array_map(fn ($field) => ['sometimes', ...$field['rules']], $this->fields());
    }

    public function values(): array
    {
        $values = [];
        foreach ($this->fields() as $key => $field) {
            $values[$key] = $this->get($key);
        }
        return $values;
    }

    public function get(string $key): string
    {
        $field = $this->fields()[$key] ?? null;
        if (!$field) return '';
        try { $value = Setting::value($key, $field['default']); }
        catch (\Throwable) { $value = $field['default']; }
        return Validator::make([$key => $value], [$key => $field['rules']])->passes() ? $value : $field['default'];
    }

    public function font(bool $pdf = false): string
    {
        $key = $this->get('theme_font');
        return $pdf ? match ($key) {
            'serif' => 'DejaVu Serif', 'mono' => 'DejaVu Sans Mono', default => 'DejaVu Sans',
        } : config('appearance.fonts')[$key];
    }

    public function rgb(string $key): string
    {
        return implode(', ', array_map('hexdec', str_split(substr($this->get($key), 1), 2)));
    }

    public function paperCss(string $kind): string
    {
        return match ($this->get($kind . '_paper')) {
            '80mm' => '80mm 297mm', '58mm' => '58mm 297mm', default => $this->get($kind . '_paper'),
        };
    }
}
