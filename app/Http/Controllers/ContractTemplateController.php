<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractTemplateRequest;
use App\Http\Requests\UpdateContractTemplateRequest;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ProjectCategory;
use App\Services\ContractService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ContractTemplateController extends Controller
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
            (new Middleware('permission:contracts_view'))->only(['index', 'show', 'preview']),
            (new Middleware('permission:contracts_edit'))->only(['create', 'store', 'edit', 'update', 'destroy']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(): View
    {
        $templates = ContractTemplate::query()->with(['creator', 'updater'])->orderByDesc('updated_at')->orderByDesc('id')->paginate(20);

        $stats = [
            'total' => ContractTemplate::query()->count(),
            'active' => ContractTemplate::query()->where('is_active', true)->count(),
            'inactive' => ContractTemplate::query()->where('is_active', false)->count(),
            'recent' => ContractTemplate::query()->where('updated_at', '>=', now()->subDays(30))->count(),
        ];

        return view('contract_templates.index', array_merge(compact('templates', 'stats'), $this->modalData()));
    }

    /**
     * Dropdown data for the add/edit template modal.
     */
    protected function modalData(): array
    {
        return [
            'projectCategories' => ProjectCategory::query()->orderBy('category_name')->get(['id', 'category_name']),
        ];
    }

    /**
     * Templates are created from the modal on the index page.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('contract-templates.index');
    }

    public function store(StoreContractTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $template = ContractTemplate::create($this->normalizePayload($data));

        return redirect()->route('contract-templates.index')->with('success', 'Contract template "' . $template->template_name . '" created successfully.');
    }

    public function show(ContractTemplate $contractTemplate): View
    {
        $contractTemplate->load(['creator', 'updater']);

        return view('contract_templates.show', array_merge(['template' => $contractTemplate], $this->modalData()));
    }

    /**
     * Templates are edited from the modal on the index/show pages.
     */
    public function edit(ContractTemplate $contractTemplate): RedirectResponse
    {
        return redirect()->route('contract-templates.show', $contractTemplate);
    }

    public function update(UpdateContractTemplateRequest $request, ContractTemplate $contractTemplate): RedirectResponse
    {
        $contractTemplate->update($this->normalizePayload($request->validated()));

        return redirect()->route('contract-templates.index')->with('success', 'Contract template updated successfully.');
    }

    public function destroy(ContractTemplate $contractTemplate): RedirectResponse
    {
        $contractTemplate->delete();

        return redirect()->route('contract-templates.index')->with('success', 'Contract template deleted successfully.');
    }

    public function preview(Request $request, ContractTemplate $contractTemplate): View
    {
        $contract = null;
        if ($request->filled('contract_id')) {
            $contract = Contract::query()->with(['member', 'group', 'project', 'branch', 'paymentMethod', 'items'])->find((int) $request->query('contract_id'));
        }

        $renderedHtml = $contract
            ? $this->contractService->renderTemplate($contract, $contractTemplate)
            : $this->contractService->sanitizeHtml((string) $contractTemplate->template_body);

        return view('contract_templates.preview', [
            'template' => $contractTemplate,
            'contract' => $contract,
            'renderedHtml' => $renderedHtml,
        ]);
    }

    protected function normalizePayload(array $data): array
    {
        $sections = $data['template_sections'] ?? null;

        if (is_string($sections)) {
            $decoded = json_decode($sections, true);
            $sections = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
        }

        if (!is_array($sections)) {
            $sections = null;
        }

        return [
            'template_name' => $data['template_name'],
            'project_type_id' => $data['project_type_id'] ?? null,
            'project_category_id' => $data['project_category_id'] ?? null,
            'template_key' => $data['template_key'] ?? null,
            'cover_page' => $data['cover_page'] ?? null,
            'template_body' => $data['template_body'],
            'contract_footer' => $data['contract_footer'] ?? null,
            'contract_signature' => $data['contract_signature'] ?? null,
            'template_sections' => $sections,
            'version' => $data['version'] ?? 1,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }
}
