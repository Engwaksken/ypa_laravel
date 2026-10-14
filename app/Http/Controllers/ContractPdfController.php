<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\ContractService;
use App\Services\PermissionService;
use App\Services\PdfAssetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Response;

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
        $contract->loadMissing(['member', 'group', 'project', 'branch', 'paymentMethod', 'items']);

        $data = $request->validate(['template_id' => ['nullable', 'integer', 'min:1'], 'download' => ['nullable', 'boolean']]);
        $template = $this->contractService->resolveTemplate($contract, isset($data['template_id']) ? (int) $data['template_id'] : null);

        $renderedHtml = $template ? $this->contractService->renderTemplate($contract, $template) : '';

        $pdf = Pdf::loadView('contracts.pdf', [
            'contract' => $contract,
            'template' => $template,
            'renderedHtml' => app(PdfAssetService::class)->localizeImages($renderedHtml),
            'totals' => $this->contractService->contractTotals($contract),
        ])->setPaper('a4');
        $filename = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($contract->contract_number ?: 'contract')) . '.pdf';

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
