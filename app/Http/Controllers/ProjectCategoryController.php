<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectCategoryRequest;
use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ProjectCategoryController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:project_categories'),
            (new Middleware('permission:project_categories_create'))->only(['store']),
            (new Middleware('permission:project_categories_edit'))->only(['update']),
            (new Middleware('permission:project_categories_delete'))->only(['destroy']),
            (new Middleware('permission:project_categories_export'))->only(['export']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $perPage = max(5, (int) $request->query('per_page', 10));
        $perPage = in_array($perPage, [5, 10, 20, 50, 100], true) ? $perPage : 10;

        $query = ProjectCategory::query()
            ->withCount('projects as projects_count')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('category_name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if ($statusFilter === 'active') {
            $query->where('status', 1);
        } elseif ($statusFilter === 'inactive') {
            $query->where('status', 0);
        }

        $categories = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total' => ProjectCategory::count(),
            'active' => ProjectCategory::where('status', 1)->count(),
            'inactive' => ProjectCategory::where('status', 0)->count(),
        ];

        return view('project-categories.index', compact(
            'categories',
            'search',
            'statusFilter',
            'perPage',
            'stats'
        ));
    }

    public function store(ProjectCategoryRequest $request): JsonResponse|RedirectResponse
    {
        $data = array_merge($request->validated(), ['created_by' => auth()->id()]);
        $category = ProjectCategory::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$category->category_name}' created successfully.",
                'id' => $category->id,
            ], 201);
        }

        return redirect()
            ->route('project-categories.index')
            ->with('success', "Category '{$category->category_name}' created successfully.");
    }

    public function update(ProjectCategoryRequest $request, ProjectCategory $projectCategory): JsonResponse|RedirectResponse
    {
        $projectCategory->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$projectCategory->category_name}' updated successfully.",
            ]);
        }

        return redirect()
            ->route('project-categories.index')
            ->with('success', "Category '{$projectCategory->category_name}' updated successfully.");
    }

    public function destroy(Request $request, ProjectCategory $projectCategory): JsonResponse|RedirectResponse
    {
        $projectsCount = Project::where('project_category_id', $projectCategory->id)->count();

        if ($projectsCount > 0) {
            $message = "Cannot delete. {$projectsCount} project(s) are linked to this category.";
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        $projectCategory->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.',
            ]);
        }

        return redirect()
            ->route('project-categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * AJAX endpoint returning a single category for the edit modal.
     */
    public function getCategoryDetails(Request $request, int $id): JsonResponse
    {
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid category id']);
        }

        $category = ProjectCategory::find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found']);
        }

        return response()->json(['success' => true, 'category' => [
            'id' => $category->id,
            'category_name' => $category->category_name,
            'description' => $category->description,
            'status' => (int) $category->status,
        ]]);
    }

    /**
     * CSV export mirroring export_project_categories.php.
     */
    public function export(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $query = ProjectCategory::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('category_name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if ($statusFilter === 'active') {
            $query->where('status', 1);
        } elseif ($statusFilter === 'inactive') {
            $query->where('status', 0);
        }

        $categories = $query->get();

        $filename = 'project_categories_export_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($categories, $filename) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($out, ['Category Name', 'Description', 'Status', 'Created At', 'Updated At']);

            foreach ($categories as $category) {
                $status = $category->status ? 'Active' : 'Inactive';
                $row = [
                    $this->sanitizeCsvValue((string) $category->category_name),
                    $this->sanitizeCsvValue((string) ($category->description ?? '')),
                    $status,
                    $category->created_at ? substr((string) $category->created_at, 0, 19) : '',
                    $category->updated_at ? substr((string) $category->updated_at, 0, 19) : '',
                ];
                fputcsv($out, $row);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function sanitizeCsvValue(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}