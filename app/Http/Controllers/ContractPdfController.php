<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\ContractService;
use App\Services\PermissionService;
use App\Services\PdfAssetService;
use App\Services\ContractPdfDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ContractPdfController extends Controller
{
    protected PermissionService $permission;

    protected ContractService $contractService;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
        $this->contractService = app(ContractService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:contracts_view'),
        ];
    }

    public function show(Request $request, Contract $contract): Response
    {
        $contract->loadMissing(['member.nextOfKin', 'member.bankDetail', 'group', 'project', 'branch', 'paymentMethod', 'items.project',
            'payments' => fn ($query) => $query->whereRaw("UPPER(COALESCE(status, '')) = 'APPROVED'")->orderBy('transaction_date')->orderBy('id'), 'payments.paymentMethod']);

        $data = $request->validate(['template_id' => ['nullable', 'integer', 'min:1'], 'download' => ['nullable', 'boolean']]);
        $template = $this->contractService->resolveTemplate($contract, isset($data['template_id']) ? (int) $data['template_id'] : null);

        $renderedHtml = $template ? $this->contractService->renderTemplate($contract, $template) : '';

        $pdf = Pdf::loadView('contracts.pdf', [
            'contract' => $contract,
            'template' => $template,
            'renderedHtml' => app(PdfAssetService::class)->localizeImages($renderedHtml),
            'totals' => $this->contractService->contractTotals($contract),
            'document' => app(ContractPdfDocumentService::class)->prepare($contract, $template, $renderedHtml),
        ])->setPaper(app(\App\Services\AppearanceService::class)->get('contract_paper'));
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(245, $canvas->get_height() - 24, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.4, 0.4, 0.4]);
        $filename = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($contract->contract_number ?: 'contract')) . '.pdf';

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function print(Request $request, Contract $contract): View
    {
        $contract->loadMissing(['member.nextOfKin', 'member.bankDetail', 'group', 'project', 'branch', 'paymentMethod', 'items.project']);
        $data = $request->validate(['template_id' => ['nullable', 'integer', 'min:1']]);
        $template = $this->contractService->resolveTemplate($contract, isset($data['template_id']) ? (int) $data['template_id'] : null);
        $html = $template ? $this->contractService->renderTemplate($contract, $template) : '';
        $sections = app(ContractPdfDocumentService::class)->sections($html);
        foreach ($sections as $key => $value) $sections[$key] = app(PdfAssetService::class)->browserImages($value);
        $group = strtolower((string) $contract->contract_for) === 'group';
        $nextOfKin = $contract->member?->nextOfKin?->first();

        return view('contracts.print', app(\App\Services\ContractPhotoService::class)->resolve($contract, true) + [
            'contract' => $contract,
            'template' => $template,
            'coverHtml' => $sections['coverHtml'],
            'bodyHtml' => $sections['bodyHtml'],
            'signatureHtml' => $sections['signatureHtml'],
            'logoUrl' => app(\App\Services\BrandingService::class)->logoUrl(),
            'partyName' => $group ? $contract->group?->group_name : $contract->member?->full_name,
            'nextOfKin' => $nextOfKin,
            'group' => $group,
        ]);
    }

}
