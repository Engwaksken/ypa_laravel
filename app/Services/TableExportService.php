<?php

namespace App\Services;

class TableExportService
{
    public function allowed(?string $route): bool
    {
        $permissions = [
            'members' => ['members_export'],
            'groups' => ['groups_export'],
            'activities' => ['activities_export'],
            'payments' => ['payments_export'],
            'receivables' => ['receivables_export'],
            'projects' => ['projects_export'],
            'project-categories' => ['project_categories_export'],
            'expenses' => ['export_expenses', 'manage_expenses'],
            'business-report' => ['export_business_report'],
            'sales-reports' => ['export_sales_report'],
            'stock-report' => ['export_stock_report'],
            'low-stock' => ['export_low_stock_report'],
            'expiry-stock' => ['export_expiry_stock_report'],
            'financial-reports' => ['export_financial_reports'],
        ];

        $module = explode('.', $route ?? '')[0];

        // Other modules have no separate export permission: page access applies.
        return ! isset($permissions[$module]) || app(PermissionService::class)->canAny($permissions[$module]);
    }
}
