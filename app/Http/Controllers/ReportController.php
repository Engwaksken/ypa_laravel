<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

abstract class ReportController extends Controller
{
    /**
     * Normalise a report date range. Defaults to the current month so
     * reports are immediately useful, and guards against malformed input.
     */
    protected function dateRange(Request $request): array
    {
        $from = trim((string) $request->query('from', now()->startOfMonth()->toDateString()));
        $to = trim((string) $request->query('to', now()->toDateString()));

        if (!$this->validDate($from)) {
            $from = now()->startOfMonth()->toDateString();
        }
        if (!$this->validDate($to)) {
            $to = now()->toDateString();
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * Resolve the branch scope. Users with all-branch rights may pick any
     * branch (0 = all); everyone else is pinned to their own branch.
     */
    protected function selectedBranch(Request $request): int
    {
        $canAll = app(PermissionService::class)->canAny(['all_branches', 'view_all_branches']);

        if ($canAll) {
            return max(0, (int) $request->query('branch', 0));
        }

        $branchId = auth()->user()->branch_id;
        abort_unless($branchId && (int) $branchId > 0, 403, 'Your account has no assigned branch.');
        return (int) $branchId;
    }

    protected function branches(): Collection
    {
        return app(\App\Services\BranchAccess::class)->branches()->get(['id', 'name']);
    }

    protected function branchName(Collection $branches, int $branchId): string
    {
        if ($branchId === 0) {
            return 'All Branches';
        }

        return $branches->firstWhere('id', $branchId)->name ?? 'Unknown Branch';
    }

    protected function can(Callable $checker): bool
    {
        return (bool) $checker(app(PermissionService::class));
    }

    protected function validDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    protected function sanitize(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    protected function moneyExport(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }

    /**
     * Build a streamed CSV download with a UTF-8 BOM and sanitised cells.
     */
    protected function streamCsv(string $filename, array $headers, Collection $rows, callable $rowMapper)
    {
        $sanitize = fn ($value) => $this->sanitize((string) ($value ?? ''));

        $callback = function () use ($headers, $rows, $rowMapper, $sanitize) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, array_map($sanitize, $headers));

            foreach ($rows as $row) {
                fputcsv($out, array_map($sanitize, $rowMapper($row)));
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function money(float $amount): string
    {
        return 'UGX ' . number_format((float) ($amount ?? 0));
    }

    protected function fmtDate(?string $date): string
    {
        if (!$date || !$this->validDate($date)) {
            return '-';
        }

        return Carbon::parse($date)->format('M d, Y');
    }
}
