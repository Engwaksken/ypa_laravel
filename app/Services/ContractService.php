<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;

class ContractService
{
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
        $templateBody = (string) ($template?->template_body ?? '');
        if ($templateBody === '') {
            return '';
        }

        $contract->loadMissing(['member', 'group', 'project', 'branch', 'paymentMethod', 'items']);

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

        $replacements = [
            '{{contract_number}}' => (string) ($contract->contract_number ?? ''),
            '{{contract_for}}' => ucfirst((string) ($contract->contract_for ?? 'member')),
            '{{member_name}}' => $memberName,
            '{{group_name}}' => $groupName,
            '{{party_name}}' => $partyName,
            '{{party_phone}}' => $partyPhone,
            '{{party_email}}' => $partyEmail,
            '{{project_name}}' => $projectName,
            '{{branch}}' => $branchName,
            '{{payment_method}}' => $paymentMethod,
            '{{payment_frequency}}' => (string) ($contract->payment_frequency ?? ''),
            '{{membership_id}}' => (string) ($contract->member->membership_id ?? ''),
            '{{group_code}}' => (string) ($contract->group->group_code ?? ''),
            '{{signing_date}}' => optional($contract->signing_date)?->format('Y-m-d') ?? '',
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
        ];

        $rendered = str_replace(array_keys($replacements), array_values($replacements), $templateBody);

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

        for ($child = $node->lastChild; $child !== null; $child = $child->previousSibling) {
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);

                if (!in_array($tag, $allowedTags, true)) {
                    $this->unwrapNode($child);
                    continue;
                }

                $this->sanitizeElementAttributes($child);
                $this->sanitizeDomNode($child);
            } elseif ($child->hasChildNodes()) {
                $this->sanitizeDomNode($child);
            }
        }
    }

    protected function sanitizeElementAttributes(\DOMElement $element): void
    {
        $allowedAttributes = ['class', 'colspan', 'href', 'rowspan', 'src', 'alt', 'title', 'style'];

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
