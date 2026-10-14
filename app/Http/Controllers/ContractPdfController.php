<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\ContractService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
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

    public function show(Request $request, Contract $contract): View|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
    {
        $contract->loadMissing(['member', 'group', 'project', 'branch', 'paymentMethod', 'items']);

        $data = $request->validate(['template_id' => ['nullable', 'integer', 'min:1']]);
        $template = $this->contractService->resolveTemplate($contract, isset($data['template_id']) ? (int) $data['template_id'] : null);

        $renderedHtml = $template ? $this->contractService->renderTemplate($contract, $template) : '';

        if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('contracts.pdf', [
                'contract' => $contract,
                'template' => $template,
                'renderedHtml' => $renderedHtml,
                'browserPrint' => false,
            ])->download(($contract->contract_number ?: 'contract') . '.pdf');
        }

        return response()->view('contracts.pdf', [
            'contract' => $contract,
            'template' => $template,
            'renderedHtml' => $renderedHtml,
            'browserPrint' => true,
        ]);
    }
}
