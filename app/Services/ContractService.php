<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;

class ContractService
{
    public function resolveTemplate(Contract $contract, ?int $requestedId = null): ?ContractTemplate
    {
        $contract->loadMissing(['project.category', 'projectData']);
        $typeId = (int) ($contract->project_type_id ?: $contract->project?->project_type_id);
        $categoryId = (int) ($contract->project_category_id ?: $contract->project?->project_category_id);
        $matching = ContractTemplate::query()
            ->where(fn ($query) => $query->whereNull('project_id')->orWhere('project_id', 0)
                ->orWhere('project_id', (int) $contract->project_id))
            ->where(fn ($query) => $query->whereNull('project_type_id')->orWhere('project_type_id', 0)
                ->orWhere('project_type_id', $typeId));

        $categoryKey = $this->categoryKey((string) ($contract->project?->category?->category_name ?: $contract->project?->project_name));
        if (in_array($categoryKey, ['honey_bee', 'bee_venom'], true) && str_contains(strtolower((string) $contract->bee_sub_type), 'venom')) {
            $categoryKey = 'bee_venom';
        }
        $matchesCategory = function (ContractTemplate $template) use ($categoryId, $categoryKey): bool {
            $raw = $template->getAttributes()['project_category_id'] ?? null;
            if (is_numeric($raw) && (int) $raw > 0) {
                return (int) $raw === $categoryId;
            }
            $legacy = trim((string) ($template->project_category ?: ($template->template_sections['_cat'] ?? '') ?: $raw));

            return $legacy === '' || $legacy === '0' || $this->categoryKey($legacy) === $categoryKey;
        };

        if ($requestedId !== null) {
            $requested = (clone $matching)->where('is_active', true)->findOrFail($requestedId);
            if (! $matchesCategory($requested)) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(ContractTemplate::class, [$requestedId]);
            }

            return $requested;
        }

        // Keep a saved version (even if retired) when it belongs to this project.
        $key = trim((string) ($contract->template_path ?: $contract->projectData?->template_key));
        if ($key !== '') {
            $saved = (clone $matching)->where(fn ($query) => $query->where('template_key', $key)
                ->orWhere('template_name', $key))->orderByDesc('version')->orderByDesc('id')->get()->first($matchesCategory);
            if ($saved) {
                return $saved;
            }
        }

        // Specific project, exact category + type, category default, type default, global default.
        return $matching->where('is_active', true)->get()->filter($matchesCategory)->sortBy(fn ($template) => [
            $template->project_id ? 0 : 1,
            (($template->getAttributes()['project_category_id'] ?? null) || $template->project_category || ($template->template_sections['_cat'] ?? null)) ? 0 : 1,
            $template->project_type_id ? 0 : 1,
            -(int) $template->version,
            -(int) $template->id,
        ])->first();
    }

    private function categoryKey(string $value): string
    {
        $key = strtolower(trim(str_replace(['-', '_'], ' ', $value)));
        if (str_contains($key, 'venom')) return 'bee_venom';
        if (str_contains($key, 'honey') || str_contains($key, 'bee')) return 'honey_bee';
        if (str_contains($key, 'planned') || str_contains($key, 'kid')) return 'planned_kid';
        if (str_contains($key, 'cdc') || str_contains($key, 'caprine')) return 'cdc';
        if (str_contains($key, 'maize')) return 'maize';
        if (str_contains($key, 'goat')) return 'goat';

        return $key;
    }

    public function nextContractNumber(): string
    {
        $prefix = 'CT-' . now()->format('Ymd') . '-';
        $next = 1;

        $last = Contract::query()
            ->where('contract_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTRING_INDEX(contract_number, "-", -1) AS UNSIGNED) DESC')
            ->first();

        if ($last && preg_match('/(\d+)$/', (string) $last->contract_number, $matches)) {
            $next = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function csvSafeValue(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    public function contractTotals(Contract $contract): array
    {
        $totalPayable = (float) ($contract->total_payable ?? $contract->contract_amount ?? $contract->total_amount ?? 0);
        $totalPaid = (float) ($contract->total_paid ?? $contract->total_amount_paid ?? $contract->amount_paid ?? 0);
        $outstanding = max(0, round($totalPayable - $totalPaid, 2));

        return [
            'total_payable' => round($totalPayable, 2),
            'total_paid' => round($totalPaid, 2),
            'total_outstanding' => $outstanding,
        ];
    }

    public function renderTemplate(Contract $contract, ?ContractTemplate $template = null): string
    {
        $template ??= $this->resolveTemplate($contract);
        $templateBody = (string) ($template?->template_body ?? '');
        if ($templateBody === '') {
            return '';
        }

        $contract->loadMissing(['member.nextOfKin', 'member.bankDetail', 'group', 'project.category', 'branch', 'paymentMethod', 'items', 'projectData']);

        $memberName = trim((string) optional($contract->member)->full_name);
        $groupName = trim((string) ($contract->group->group_name ?? ''));
        $projectName = trim((string) ($contract->project->project_name ?? ''));
        $branchName = trim((string) ($contract->branch->name ?? ''));
        $paymentMethod = trim((string) ($contract->paymentMethod->method_name ?? ''));
        $partyName = strtolower((string) ($contract->contract_for ?? 'member')) === 'group' ? $groupName : $memberName;
        $partyPhone = strtolower((string) ($contract->contract_for ?? 'member')) === 'group'
            ? (string) ($contract->group->telephone ?? '')
            : (string) ($contract->member->telephone1 ?? '');
        $partyEmail = strtolower((string) ($contract->contract_for ?? 'member')) === 'group'
            ? (string) ($contract->group->email ?? '')
            : (string) ($contract->member->email ?? '');

        $item = $contract->items->first();
        $totals = $this->contractTotals($contract);
        $sidecar = $contract->projectData?->data_json ?? [];
        $nextOfKin = $contract->member?->nextOfKin->first();
        $bank = $contract->member?->bankDetail;
        $bankBranch = (string) ($bank?->bank_branch ?? $contract->member?->bank_branch ?? '');
        $contractType = (string) ($contract->contract_type_name ?? $sidecar['contract_type_name'] ?? $template->template_sections['title'] ?? 'Contract Agreement');

        $replacements = [
            '{{contract_number}}' => (string) ($contract->contract_number ?? ''),
            '{{contract_for}}' => ucfirst((string) ($contract->contract_for ?? 'member')),
            '{{member_name}}' => $memberName,
            '{{group_name}}' => $groupName,
            '{{party_name}}' => $partyName,
            '{{party_phone}}' => $partyPhone,
            '{{party_email}}' => $partyEmail,
            '{{project_name}}' => $projectName,
            '{{branch}}' => $bankBranch ?: $branchName,
            '{{branch_name}}' => $branchName,
            '{{payment_method}}' => $paymentMethod,
            '{{payment_frequency}}' => (string) ($contract->payment_frequency ?? ''),
            '{{membership_id}}' => (string) ($contract->member->membership_id ?? ''),
            '{{group_code}}' => (string) ($contract->group->group_code ?? ''),
            '{{signing_date}}' => optional($contract->signing_date)?->format('jS \d\a\y \o\f F, Y') ?? '',
            '{{start_date}}' => optional($contract->start_date)?->format('Y-m-d') ?? '',
            '{{end_date}}' => optional($contract->end_date)?->format('Y-m-d') ?? '',
            '{{contract_amount}}' => number_format((float) ($contract->contract_amount ?? 0), 2),
            '{{total_payable}}' => number_format($totals['total_payable'], 2),
            '{{total_paid}}' => number_format($totals['total_paid'], 2),
            '{{outstanding}}' => number_format($totals['total_outstanding'], 2),
            '{{item_name}}' => (string) ($item->item_name ?? ''),
            '{{item_type}}' => (string) ($item->item_type ?? ''),
            '{{quantity}}' => (string) ($item->quantity ?? ''),
            '{{unit_name}}' => (string) ($item->unit_name ?? ''),
            '{{unit_price}}' => number_format((float) ($item->unit_price ?? 0), 2),
            '{{total_price}}' => number_format((float) ($item->total_price ?? 0), 2),
            '{{monthly_return}}' => number_format((float) ($item->monthly_return ?? 0), 2),
            '{{monthly_payout_amount}}' => number_format((float) ($item->monthly_payout_amount ?? 0), 2),
            '{{total_hives}}' => number_format((float) ($item->total_hives ?? 0), 2),
            '{{project_category}}' => (string) ($contract->project?->category?->category_name ?? ''),
            '{{project_type}}' => $contractType,
            '{{project_type_name}}' => $contractType,
            '{{contract_type_name}}' => $contractType,
            '{{farmer_name}}' => $partyName,
            '{{farmer_phone}}' => $partyPhone,
            '{{membership_number}}' => (string) ($contract->member?->membership_id ?? ''),
            '{{reg_number}}' => (string) ($contract->contract_number ?? ''),
            '{{registration_number}}' => (string) ($contract->contract_number ?? ''),
            '{{nin}}' => (string) ($contract->member?->nin ?? ''),
            '{{address}}' => (string) ($contract->member?->address ?? ''),
            '{{region}}' => (string) ($contract->region ?? $contract->member?->region ?? ''),
            '{{district}}' => (string) ($contract->district ?? $contract->member?->district ?? ''),
            '{{amount_paid}}' => number_format($totals['total_paid'], 2),
            '{{amount_in_words}}' => (string) ($contract->amount_in_words ?? ''),
            '{{number_of_units}}' => (string) ($contract->expected_units ?? $contract->hives ?? $item?->quantity ?? ''),
            '{{acreage}}' => (string) ($sidecar['maize_acres'] ?? ''),
            '{{monthly_payment}}' => number_format((float) ($contract->monthly_payout_amount ?? $item?->monthly_payout_amount ?? 0), 2),
            '{{quarterly_payment}}' => (string) ($sidecar['quarterly_payment'] ?? ''),
            '{{seasonal_harvest}}' => (string) ($sidecar['seasonal_harvest'] ?? ''),
            '{{bags_per_season}}' => (string) ($sidecar['bags_per_season'] ?? ''),
            '{{expected_kids}}' => (string) ($sidecar['expected_kids'] ?? ''),
            '{{harvest_amount}}' => number_format((float) ($contract->projected_harvest_amount ?? 0), 2),
            '{{price_per_goat}}' => (string) ($sidecar['goat_purchase_price'] ?? $sidecar['cdc_purchase_price'] ?? ''),
            '{{price_per_hive}}' => number_format((float) ($item?->unit_price ?? 0), 2),
            '{{guardian_name}}' => (string) ($sidecar['guardian_name'] ?? ''),
            '{{guardian_phone}}' => (string) ($sidecar['guardian_phone'] ?? ''),
            '{{planned_kid_name}}' => (string) ($sidecar['planned_kid_name'] ?? ''),
            '{{planned_kid_age}}' => (string) ($sidecar['planned_kid_age'] ?? ''),
            '{{next_of_kin_name}}' => (string) ($nextOfKin?->full_name ?? ''),
            '{{next_of_kin_phone}}' => (string) ($nextOfKin?->phone ?? $nextOfKin?->telephone1 ?? ''),
            '{{bank_name}}' => (string) ($bank?->bank_name ?? $contract->member?->bank_name ?? ''),
            '{{account_name}}' => (string) ($bank?->account_name ?? $contract->member?->bank_account_name ?? ''),
            '{{account_number}}' => (string) ($bank?->bank_account ?? $contract->member?->bank_account ?? ''),
            '{{director_name}}' => (string) ($contract->director_name ?? ''),
            '{{bank_branch}}' => $bankBranch,
            '{{farmer_email}}' => $partyEmail,
            '{{project_code}}' => (string) ($contract->project_code ?: $contract->project?->project_code),
            '{{item_quantity}}' => (string) ($item?->quantity ?? ''),
            '{{total_scheduled}}' => number_format($totals['total_payable'], 2),
            '{{total_outstanding}}' => number_format($totals['total_outstanding'], 2),
            '{{contract_amount_paid}}' => number_format((float) ($contract->contract_amount_paid ?? $totals['total_paid']), 2),
            '{{contract_amount_outstanding}}' => number_format($totals['total_outstanding'], 2),
            '{{status}}' => (string) ($contract->status ?? ''),
            '{{workflow_status}}' => (string) ($contract->workflow_status ?? ''),
        ];
        foreach (['management_model', 'investment_period', 'duration', 'duration_months', 'expected_quantity',
            'expected_units', 'goats_purchased', 'hives_purchased', 'total_goats', 'total_acreages', 'acreages_purchased',
            'units', 'units_1dp', 'units_2dp', 'unit_label', 'kg_per_bag', 'harvest_frequency', 'mobile_money',
            'projected_harvest_amount', 'projected_harvest_balance', 'purchase_fee', 'purchase_price', 'cost_per_kg',
            'harvest_per_bag', 'harvest_cash_per_acre', 'seasonal_harvest_quantity', 'total_quantity_at_harvest',
            'seasonal_harvest_amount', 'amount_at_maturity', 'final_payout', 'quarterly_payout', 'rep_name'] as $key) {
            $value = $sidecar[$key] ?? $contract->getAttribute($key) ?? $item?->getAttribute($key) ?? '';
            $replacements['{{' . $key . '}}'] = is_scalar($value) ? (string) $value : '';
        }

        // Legacy templates embed these structural blocks; newer ones store them separately.
        $footer = (string) ($template->contract_footer ?? '');
        $signature = (string) ($template->contract_signature ?? '');
        $body = strtr($templateBody, ['{{contract_footer}}' => $footer, '{{contract_signature}}' => $signature]);
        if ($footer !== '' && !str_contains($templateBody, '{{contract_footer}}') && !str_contains($templateBody, $footer)) {
            $body .= $footer;
        }
        if ($signature !== '' && !str_contains($templateBody, '{{contract_signature}}') && !str_contains($templateBody, $signature)) {
            $body .= $signature;
        }
        $cover = trim((string) ($template->cover_page ?? ''));
        if ($cover !== '') {
            $body = '<section class="contract-cover-custom" style="page-break-after:always;">' . $cover . '</section>' . $body;
        }
        $body = strtr($body, [
            '{{org_signature_block}}' => view('contracts.documents.organization-signatures', compact('contract'))->render(),
            '{{director_signature}}' => '<span>Signature: ___________________________</span>',
            '{{receipt_section}}' => $contract->receipt_number
                ? '<p><strong>Receipt:</strong> ' . e($contract->receipt_number) . '</p>' : '',
        ]);
        // Values are text, not template markup. Escaping prevents data-driven HTML injection.
        foreach (array_merge($contract->project_calculation['inputs'] ?? [], $contract->project_calculation['results'] ?? []) as $key => $value) {
            $replacements['{{project.' . $key . '}}'] = (string) $value;
        }
        $rendered = strtr($body, array_map(fn ($value) => e($value), $replacements));

        return $this->sanitizeHtml($rendered);
    }

    public function sanitizeHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="contract-sanitizer-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $root = $dom->getElementById('contract-sanitizer-root');
        if (!$root) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return '';
        }

        $this->sanitizeDomNode($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $output;
    }

    protected function sanitizeDomNode(\DOMNode $node): void
    {
        $allowedTags = [
            'a', 'article', 'b', 'blockquote', 'br', 'div', 'em', 'footer', 'h1', 'h2', 'h3', 'h4',
            'h5', 'h6', 'header', 'hr', 'i', 'img', 'li', 'ol', 'p', 'section', 'span', 'strong',
            'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u', 'ul',
        ];

        $child = $node->lastChild;

        while ($child !== null) {
            $previousSibling = $child->previousSibling;

            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);

                if (!in_array($tag, $allowedTags, true)) {
                    $this->sanitizeDomNode($child);
                    $this->unwrapNode($child);
                    $child = $previousSibling;
                    continue;
                }

                $this->sanitizeElementAttributes($child);
                $this->sanitizeDomNode($child);
            } elseif ($child->hasChildNodes()) {
                $this->sanitizeDomNode($child);
            }

            $child = $previousSibling;
        }
    }

    protected function sanitizeElementAttributes(\DOMElement $element): void
    {
        $allowedAttributes = ['class', 'colspan', 'href', 'rowspan', 'src', 'alt', 'title', 'style', 'type', 'start', 'value', 'width', 'height'];

        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);
            if (!$attribute) {
                continue;
            }

            $name = strtolower($attribute->name);
            $value = (string) $attribute->value;

            if (!in_array($name, $allowedAttributes, true)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if (str_starts_with($name, 'on')) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if (($name === 'href' || $name === 'src') && !$this->isSafeUrl($value)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if ($name === 'style') {
                $cleanStyle = preg_replace('/expression\s*\([^)]*\)/i', '', $value) ?? '';
                $cleanStyle = preg_replace('/url\(\s*["\']?\s*javascript:[^)]*\)/i', '', $cleanStyle) ?? '';
                $cleanStyle = preg_replace('/javascript:/i', '', $cleanStyle) ?? '';

                if (trim($cleanStyle) === '') {
                    $element->removeAttributeNode($attribute);
                } else {
                    $element->setAttribute($name, $cleanStyle);
                }
            }
        }
    }

    protected function unwrapNode(\DOMNode $node): void
    {
        if (!$node->parentNode) {
            return;
        }

        while ($node->firstChild) {
            $node->parentNode->insertBefore($node->firstChild, $node);
        }

        $node->parentNode->removeChild($node);
    }

    protected function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return true;
        }

        return preg_match('~^(https?:|mailto:|/|#)~i', $url) === 1;
    }

    public function resolveTemplateSections(?array $sections, string $fallback = ''): array
    {
        if (!is_array($sections) || $sections === []) {
            return $fallback !== '' ? ['fallback' => $fallback] : [];
        }

        return $sections;
    }
}
