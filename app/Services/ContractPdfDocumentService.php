<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Setting;

class ContractPdfDocumentService
{
    public function prepare(Contract $contract, ?ContractTemplate $template, string $html): array
    {
        $assets = app(PdfAssetService::class);
        $storedSignature = '';
        if ($template && trim((string) $template->contract_signature) !== '') {
            $signatureTemplate = clone $template;
            $signatureTemplate->template_body = $template->contract_signature;
            $signatureTemplate->contract_signature = $signatureTemplate->contract_footer = $signatureTemplate->cover_page = '';
            $candidate = app(ContractService::class)->renderTemplate($contract, $signatureTemplate);
            if ($candidate !== '' && str_contains($html, $candidate)) {
                $html = str_replace($candidate, '', $html);
                $storedSignature = $candidate;
            }
        }
        $parts = $this->sections($html);
        if ($storedSignature !== '') $parts['signatureHtml'] .= $storedSignature;
        foreach ($parts as $key => $value) $parts[$key] = $assets->localizeImages($value);
        try {
            $logo = Setting::asset('site_logo', 'images/logo.png');
        } catch (\Throwable) {
            $logo = 'images/logo.png';
        }
        $nextOfKin = $contract->member?->nextOfKin?->first();
        $group = strtolower((string) $contract->contract_for) === 'group';
        $stockSignature = app(ContractService::class)->sanitizeHtml(view('contracts.documents.organization-signatures', compact('contract'))->render());
        $standardSignatures = trim(preg_replace('/\s+/', ' ', $parts['signatureHtml'])) === trim(preg_replace('/\s+/', ' ', $stockSignature));

        return $parts + [
            'logo' => $assets->localImageUri($logo),
            'partyName' => $group ? $contract->group?->group_name : $contract->member?->full_name,
            'partyCode' => $group ? $contract->group?->group_code : $contract->member?->membership_id,
            'partyLabel' => $group ? 'GROUP REPRESENTATIVE' : 'FARMER',
            'partyPhoto' => $assets->localImageUri($contract->farmer_photo ?: $contract->member?->member_photo),
            'kinPhoto' => $assets->localImageUri($contract->nok_photo ?: $nextOfKin?->photo ?: $nextOfKin?->nok_photo ?: $nextOfKin?->photo_path),
            'mode' => $contract->management_model ?: ($template?->template_sections['subtitle'] ?? ''),
            'standardSignatures' => $standardSignatures,
            'warnings' => $this->missingDetails($contract),
            'integrityWarnings' => app(ContractIntegrityService::class)->warnings($contract),
            'receipt' => $this->receipt($contract),
        ];
    }

    public function missingDetails(Contract $contract): array
    {
        if (strtolower((string) $contract->contract_for) === 'group') return [];
        $bank = $contract->member?->bankDetail;
        $warnings = [];
        if (! $contract->member?->nin) $warnings[] = 'Member NIN';
        if (! ($bank?->bank_name ?: $contract->member?->bank_name)) $warnings[] = 'Bank name';
        if (! ($bank?->bank_branch ?: $contract->member?->bank_branch)) $warnings[] = 'Bank branch';
        return $warnings;
    }

    /** Separate structural blocks without rewriting or regenerating legal clauses. */
    public function sections(string $html): array
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="pdf-document-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $dom->getElementById('pdf-document-root');
        $cover = '';
        $xpath = new \DOMXPath($dom);
        $covers = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " contract-cover-custom ") or contains(concat(" ", normalize-space(@class), " "), " cover-page ")]');
        foreach (iterator_to_array($covers) as $node) {
            if ($node->parentNode) { $cover .= $dom->saveHTML($node); $node->parentNode->removeChild($node); }
        }
        $bankHeadingSeen = false;
        foreach (iterator_to_array($xpath->query('//h2|//h3|//h4|//p')) as $node) {
            $text = trim(preg_replace('/\s+/u', ' ', $node->textContent));
            if (preg_match('/^(?:Farmer[’\x27]s|Client[’\x27]s) acknowledg(?:e)?ment of bank details$/iu', $text)) {
                if ($bankHeadingSeen) { $node->parentNode?->removeChild($node); continue; }
                $bankHeadingSeen = true;
            }
            if (preg_match('/^\d+\.\s+[A-Z][A-Z\s\/&()-]+$/', $text)) {
                $node->setAttribute('class', trim($node->getAttribute('class') . ' keep-heading'));
            }
        }
        // Remove only a stray leading ')' in an alphabetic list, never clause content.
        foreach ($xpath->query('//ol[@type="a"]/li') as $node) {
            if ($node->firstChild instanceof \DOMText && preg_match('/^\s*\)\s*/', $node->firstChild->nodeValue)) {
                $node->firstChild->nodeValue = preg_replace('/^\s*\)\s*/', '', $node->firstChild->nodeValue);
            }
        }
        [$body, $signature] = $root ? $this->partition($root, $dom) : ['', ''];
        libxml_clear_errors(); libxml_use_internal_errors($previous);

        return ['coverHtml' => $cover, 'bodyHtml' => $body, 'signatureHtml' => $signature];
    }

    private function partition(\DOMNode $parent, \DOMDocument $dom): array
    {
        $body = $signature = '';
        $inSignature = false;
        foreach ($parent->childNodes as $node) {
            $text = trim($node->textContent);
            $classes = $node instanceof \DOMElement ? $node->getAttribute('class') : '';
            $marker = $node instanceof \DOMElement && (preg_match('/(?:^|\s)(?:manual-signing-block|signature-block|contract-signature)(?:\s|$)/', $classes)
                || preg_match('/^IN WITNESS WHEREOF\b/i', $text));
            if ($inSignature || $marker) {
                $inSignature = true; $signature .= $dom->saveHTML($node);
            } elseif ($node instanceof \DOMElement && $node->hasChildNodes()) {
                [$innerBody, $innerSignature] = $this->partition($node, $dom);
                if ($innerSignature !== '') {
                    $shell = $node->cloneNode(false);
                    $opening = preg_replace('/<\/' . preg_quote($node->tagName, '/') . '>$/', '', $dom->saveHTML($shell));
                    if ($innerBody !== '') $body .= $opening . $innerBody . '</' . $node->tagName . '>';
                    $signature .= $opening . $innerSignature . '</' . $node->tagName . '>';
                    $inSignature = true;
                } else $body .= $dom->saveHTML($node);
            } else $body .= $dom->saveHTML($node);
        }
        return [$body, $signature];
    }

    public function receipt(Contract $contract): array
    {
        // Pending, rejected and soft-deleted transactions must not become official receipts.
        $payments = $contract->payments->filter(fn ($payment) => strtoupper((string) $payment->status) === 'APPROVED' && ! $payment->deleted_at)->values();
        $rows = $payments->map(function ($payment): array {
            $components = [
                'membership' => (float) $payment->membership_fee_paid,
                'registration' => (float) $payment->registration_fee_paid,
                'admin' => (float) $payment->admin_fee_paid,
                'contract' => (float) $payment->contract_amount_paid,
            ];
            $paid = (float) ($payment->total_amount_paid ?? 0);
            $componentTotal = round(array_sum($components), 2);
            if ($paid <= 0) $paid = $componentTotal > 0 ? $componentTotal : (float) $payment->amount;
            return $components + ['date' => $payment->transaction_date ?? $payment->created_at,
                'conflict' => $componentTotal > 0 && abs(round($paid, 2) - $componentTotal) >= 0.01,
                'total' => round($paid, 2), 'method' => $payment->paymentMethod?->method_name,
                'reference' => $payment->reference ?: $payment->receipt_number ?: $payment->transaction_number];
        });
        // These are recorded totals, not fees inferred from today's project settings.
        $scheduled = $contract->total_payable ?? $contract->total_amount ?? $contract->contract_amount
            ?? $payments->max('total_amount') ?? 0;
        $paid = round($rows->sum('total'), 2);

        return ['rows' => $rows, 'scheduled' => round((float) $scheduled, 2), 'paid' => $paid,
            'conflict' => $rows->contains(fn ($row) => $row['conflict']),
            'outstanding' => max(0.0, round((float) $scheduled - $paid, 2))];
    }
}
