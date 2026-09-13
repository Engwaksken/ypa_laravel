<?php

namespace App\Services;

use App\Models\RolePermission;
use App\Models\User;

/**
 * Central permission engine for YPA.
 *
 * Faithful port of the legacy auth.php / nav.php permission logic:
 *  - role normalisation (incl. the preserved "supper_admin" spelling)
 *  - built-in default permissions per role (auth_default_permissions)
 *  - DB-backed role_permissions with the "__configured__" marker making the
 *    stored selection authoritative once saved from the permissions screen
 *  - the sidebar navigation role map (nav_permission_role_map)
 *  - can() / canAny() / canAll() / roleAllowed() checks
 */
class PermissionService
{
    public const CONFIG_MARKER = '__configured__';

    /** @var array<string, array<int, string>> per-request cache of effective permissions by role */
    protected static array $effectiveCache = [];

    /* ============================================================
       Role lists
    ============================================================ */

    public function allRoles(): array
    {
        return [
            'director',
            'manager',
            'supper_admin',
            'admin',
            'head_records',
            'records_officer',
            'finance_lead',
            'accountant',
            'payment_harvest',
            'head_procurement',
            'assistant_procurement',
            'member',
            'customer',
        ];
    }

    public function adminRoles(): array
    {
        return ['supper_admin', 'admin'];
    }

    public function managementRoles(): array
    {
        return [
            'director',
            'manager',
            'supper_admin',
            'admin',
            'head_records',
            'records_officer',
            'finance_lead',
            'accountant',
            'payment_harvest',
            'head_procurement',
            'assistant_procurement',
        ];
    }

    public function dashboardRoles(): array
    {
        return [
            'director',
            'manager',
            'supper_admin',
            'admin',
            'head_records',
            'records_officer',
            'finance_lead',
            'accountant',
            'payment_harvest',
        ];
    }

    public function superRoles(): array
    {
        return ['supper_admin'];
    }

    /* ============================================================
       Normalisation
    ============================================================ */

    public function normalizeRole(mixed $role): string
    {
        $role = strtolower(trim((string) $role));
        $role = preg_replace('/_+/', '_', str_replace(['-', ' '], '_', $role)) ?: '';

        return match ($role) {
            '1' => 'director',
            '2' => 'manager',
            '3' => 'records_officer',
            '4' => 'accountant',

            'superadmin', 'super_admin', 'supperadmin', 'supper_admin' => 'supper_admin',
            'administrator' => 'admin',

            'headrecords', 'head_record', 'head_records', 'head_of_records',
            'head_officer', 'head_officers' => 'head_records',

            'record_officer', 'records_officer', 'records',
            'records_officers' => 'records_officer',

            'financelead', 'finance_lead', 'finance' => 'finance_lead',

            'paymentharvest', 'payment_harvest', 'harvest_payment',
            'payment_(harvest)', 'payment__harvest_' => 'payment_harvest',

            'headprocurement', 'head_procurement',
            'procurement_head' => 'head_procurement',

            'assistantprocurement', 'assistant_procurement',
            'procurement_assistant' => 'assistant_procurement',

            default => $role,
        };
    }

    public function normalizePermission(mixed $permission): string
    {
        $permission = strtolower(trim((string) $permission));

        return preg_replace(
            '/_+/',
            '_',
            str_replace(['-', ' '], '_', $permission)
        ) ?: '';
    }

    public function isValidRole(mixed $role): bool
    {
        return in_array($this->normalizeRole($role), $this->allRoles(), true);
    }

    /* ============================================================
       Permission groups (port of auth_permission_groups)
    ============================================================ */

    public function permissionGroups(): array
    {
        return [
            'dashboard' => [
                'view_dashboard',
            ],

            'administration' => [
                'manage_users',
                'manage_permissions',
                'manage_settings',
            ],

            'orders' => [
                'manage_orders',
            ],

            'notifications' => [
                'notifications',
                'notification',
                'view_notifications',
                'manage_notifications',
            ],

            'membership_view' => [
                'membership',
                'members',
                'members_view',
                'view_members',
                'search_members',
                'groups',
                'groups_view',
                'view_groups',
                'participants',
                'view_participants',
            ],

            'membership_manage' => [
                'manage_membership',
                'members_register',
                'create_members',
                'members_edit',
                'edit_members',
                'members_delete',
                'delete_members',
                'members_export',
                'export_members',
                'groups_create',
                'create_groups',
                'groups_edit',
                'edit_groups',
                'groups_delete',
                'delete_groups',
                'groups_export',
                'export_groups',
                'create_participants',
                'edit_participants',
                'delete_participants',
                'participant_comments',
                'participant_status',
                'participant_items',
                'export_participants',
            ],

            'contracts_view' => [
                'contracts',
                'contracts_view',
                'view_contracts',
                'contracts_export',
                'export_contracts',
                'contracts_print',
                'print_contracts',
                'contracts_pdf',
                'contracts_csv',
                'contracts_download',
                'download_contracts',
            ],

            'contracts_create' => [
                'contracts_create',
                'create_contracts',
                'create_contract',
                'new_contract',
            ],

            'contracts_edit' => [
                'contracts_edit',
                'edit_contracts',
                'edit_contract',
                'update_contracts',
                'update_contract',
            ],

            'contracts_delete' => [
                'contracts_delete',
                'delete_contracts',
                'delete_contract',
            ],

            'termination_view' => [
                'termination',
                'contract_termination',
                'contract_termination_view',
                'view_contract_termination',
                'contract_termination_export',
                'export_contract_termination',
                'contract_termination_print',
                'print_contract_termination',
                'contract_termination_pdf',
                'contract_termination_download',
            ],

            'termination_manage' => [
                'contract_termination_create',
                'create_contract_termination',
                'contract_termination_edit',
                'edit_contract_termination',
                'contract_termination_delete',
                'delete_contract_termination',
                'contract_termination_approve',
                'contract_termination_reject',
            ],

            'harvest_view' => [
                'harvests',
                'harvests_view',
                'view_harvests',
                'harvest_management',
                'harvest_management_view',
                'harvests_export',
                'export_harvests',
                'harvests_print',
                'print_harvests',
                'harvest_management_export',
                'harvest_management_print',
                'harvests_pdf',
                'harvests_download',
            ],

            'harvest_manage' => [
                'harvests_create',
                'create_harvests',
                'harvests_edit',
                'edit_harvests',
                'harvests_delete',
                'delete_harvests',
            ],

            'harvest_due_full' => [
                'harvest_due',
                'harvest_due_view',
                'view_harvest_due',
                'harvest_due_create',
                'create_harvest_due',
                'harvest_due_edit',
                'edit_harvest_due',
                'harvest_due_delete',
                'delete_harvest_due',
                'harvest_due_export',
                'export_harvest_due',
                'harvest_due_print',
                'print_harvest_due',
                'record_harvest',
                'record_harvests',
                'harvest_record',
                'harvests_record',
            ],

            'harvest_requests_view' => [
                'harvest_requests',
                'view_harvest_requests',
                'harvest_request_view',
                'harvest_requests_view',
                'harvest_management_requests',
                'harvest_request_list',
                'harvest_requests_list',
                'manage_harvest_requests',
            ],

            'harvest_requests_review' => [
                'review_harvest_requests',
                'review_harvest_request',
                'harvest_requests_review',
                'harvest_request_review',
                'harvest_review',
            ],

            'harvest_requests_approve' => [
                'approve_harvest_requests',
                'approve_harvest_request',
                'harvest_requests_approve',
                'harvest_request_approve',
                'harvest_approve',
            ],

            'harvest_requests_pay' => [
                'pay_harvest_requests',
                'harvest_requests_pay',
                'harvest_pay',
            ],

            'harvest_requests_reject' => [
                'reject_harvest_requests',
                'reject_harvest_request',
                'harvest_requests_reject',
                'harvest_request_reject',
                'harvest_reject',
            ],

            'payments_view' => [
                'payments',
                'payments_view',
                'view_payments',
                'payments_search',
                'search_payments',
                'payments_export',
                'export_payments',
                'payments_print',
                'print_payments',
                'payments_receipt',
            ],

            'payments_maintain' => [
                'payments_edit',
                'edit_payments',
                'payments_update',
                'update_payments',
                'payments_approve',
                'approve_payments',
                'payments_reject',
                'reject_payments',
                'payments_reconcile',
                'reconcile_payments',
            ],

            'new_payments' => [
                'new_payments',
                'new_payment',
                'payments_new',
                'payment_new',
                'create_payments',
                'create_payment',
                'payments_create',
                'payment_create',
                'process_payment',
                'process_payments',
                'add_payment',
                'add_payments',
                'payment_add',
                'payments_add',
            ],

            'receivables_view' => [
                'receivables',
                'view_receivables',
                'manage_receivables',
            ],

            'receivables_create' => [
                'create_receivables',
                'add_receivables',
                'receivables_create',
            ],

            'receivables_edit' => [
                'edit_receivables',
                'update_receivables',
                'receivables_edit',
            ],

            'receivables_delete' => [
                'delete_receivables',
                'remove_receivables',
                'receivables_delete',
            ],

            'receivables_export' => [
                'export_receivables',
                'download_receivables',
                'print_receivables',
                'receivables_print',
            ],

            'mobilizers_full' => [
                'mobilizers',
                'mobilizers_view',
                'view_mobilizers',
                'access_mobilizers',
                'mobilizers_create',
                'create_mobilizers',
                'add_mobilizers',
                'mobilizers_edit',
                'edit_mobilizers',
                'mobilizers_delete',
                'delete_mobilizers',
                'mobilizers_export',
                'export_mobilizers',
                'mobilizers_print',
                'print_mobilizers',
            ],

            'pos_full' => [
                'pos',
                'pos_view',
                'view_pos',
                'manage_pos',
                'point_of_sale',
                'point_of_sales',
                'non_membership_pos',
                'non_membership',

                'manage_sales',
                'sales',
                'sales_view',
                'view_sales',

                'sales_create',
                'create_sales',
                'new_sale',
                'add_sale',

                'sales_edit',
                'edit_sales',
                'update_sales',

                'sales_delete',
                'delete_sales',

                'sales_export',
                'export_sales',

                'invoice_save',
                'receipt_save',

                'invoice_view',
                'view_invoice',
                'invoice_print',
                'print_invoice',

                'receipt_view',
                'view_receipt',
                'receipt_print',
                'print_receipt',
            ],

            'products_view' => [
                'products',
                'products_view',
                'view_products',
                'manage_stock',
                'stock',
                'stock_view',
                'view_stock',
                'stock_report',
                'view_stock_report',
                'stock_reports',
            ],

            'products_manage' => [
                'manage_products',
                'products_create',
                'create_products',
                'products_edit',
                'edit_products',
                'products_delete',
                'delete_products',
                'products_import',
                'bulk_products',
                'stock_create',
                'stock_edit',
                'stock_delete',
                'stock_import',
                'categories',
                'manage_categories',
            ],

            'expenses_view' => [
                'expenses',
                'view_expenses',
            ],

            'expenses_manage' => [
                'manage_expenses',
                'create_expenses',
                'edit_expenses',
                'delete_expenses',
                'export_expenses',
                'expenses_import',
            ],

            'all_branches' => [
                'all_branches',
                'view_all_branches',
            ],

            'reports' => [
                'financial_reports',
                'financial_reports_export',
                'stock_reports',
                'stock_report',
                'view_stock_report',
                'export_stock_report',
                'print_stock_report',
                'expiry_stock',
                'expiry_stock_report',
                'view_expiry_stock',
                'export_expiry_stock',
                'low_stock_report',
            ],

            'projects' => [
                'projects',
                'projects_view',
                'view_projects',
            ],

            'projects_manage' => [
                'projects_create',
                'create_projects',
                'projects_edit',
                'edit_projects',
                'projects_delete',
                'delete_projects',
                'projects_export',
                'export_projects',
            ],

            'project_categories' => [
                'project_categories',
                'manage_project_categories',
                'projects_categories',
                'project_categories_manage',
                'project_categories_create',
                'project_categories_edit',
                'project_categories_delete',
                'project_categories_export',
            ],

            'activities' => [
                'activities',
                'activities_view',
                'activities_create',
                'create_activities',
                'activities_edit',
                'edit_activities',
                'activities_delete',
                'delete_activities',
                'activities_export',
                'export_activities',
                'manage_activities',
                'meetings',
                'meetings_view',
                'meetings_create',
                'create_meetings',
                'meetings_edit',
                'edit_meetings',
                'meetings_delete',
                'delete_meetings',
                'meetings_manage',
                'manage_meetings',
            ],
        ];
    }

    public function group(string $name): array
    {
        return $this->permissionGroups()[$name] ?? [];
    }

    protected function mergePermissions(array ...$sets): array
    {
        $out = [];

        foreach ($sets as $set) {
            foreach ($set as $permission) {
                $permission = $this->normalizePermission($permission);
                if ($permission !== '') {
                    $out[] = $permission;
                }
            }
        }

        return array_values(array_unique($out));
    }

    /* ============================================================
       Default permissions per role (port of auth_default_permissions)
    ============================================================ */

    public function defaultPermissions(string $role): array
    {
        $role = $this->normalizeRole($role);

        if ($role === 'supper_admin') {
            return ['*'];
        }

        return match ($role) {
            'director' => $this->mergePermissions(
                $this->group('dashboard'),
                $this->group('orders'),
                $this->group('notifications'),
                $this->group('membership_view'),
                $this->group('membership_manage'),
                $this->group('contracts_view'),
                $this->group('contracts_create'),
                $this->group('contracts_edit'),
                $this->group('contracts_delete'),
                $this->group('termination_view'),
                $this->group('termination_manage'),
                $this->group('harvest_view'),
                $this->group('harvest_manage'),
                $this->group('harvest_due_full'),
                $this->group('harvest_requests_view'),
                $this->group('harvest_requests_review'),
                $this->group('harvest_requests_approve'),
                $this->group('harvest_requests_reject'),
                $this->group('payments_view'),
                $this->group('payments_maintain'),
                $this->group('receivables_view'),
                $this->group('receivables_create'),
                $this->group('receivables_edit'),
                $this->group('receivables_delete'),
                $this->group('receivables_export'),
                $this->group('mobilizers_full'),
                $this->group('expenses_view'),
                ['export_expenses'],
                $this->group('all_branches'),
                $this->group('reports'),
                $this->group('projects'),
                $this->group('projects_manage'),
                $this->group('project_categories'),
                $this->group('activities'),
                $this->group('products_view'),
             $this->group('products_manage')
             ),

            'manager' => $this->mergePermissions(
                $this->group('dashboard'),
                $this->group('orders'),
                $this->group('notifications'),
                $this->group('membership_view'),
                $this->group('membership_manage'),
                $this->group('contracts_view'),
                $this->group('contracts_create'),
                $this->group('contracts_edit'),
                $this->group('contracts_delete'),
                $this->group('termination_view'),
                $this->group('termination_manage'),
                $this->group('harvest_view'),
                $this->group('harvest_manage'),
                $this->group('harvest_due_full'),
                $this->group('harvest_requests_view'),
                $this->group('harvest_requests_review'),
                $this->group('harvest_requests_reject'),
                $this->group('payments_view'),
                $this->group('payments_maintain'),
                $this->group('receivables_view'),
                $this->group('receivables_create'),
                $this->group('receivables_edit'),
                $this->group('receivables_export'),
                $this->group('expenses_view'),
                ['export_expenses'],
                $this->group('all_branches'),
                $this->group('reports'),
                $this->group('projects'),
                $this->group('projects_manage'),
                $this->group('project_categories'),
                $this->group('activities'),
                $this->group('products_view'),
                $this->group('products_manage')
            ),

            'admin' => $this->mergePermissions(
                $this->group('dashboard'),
                $this->group('orders'),
                $this->group('administration'),
                $this->group('notifications'),
                $this->group('membership_view'),
                $this->group('membership_manage'),
                $this->group('contracts_view'),
                $this->group('contracts_create'),
                $this->group('contracts_edit'),
                $this->group('contracts_delete'),
                $this->group('termination_view'),
                $this->group('termination_manage'),
                $this->group('harvest_view'),
                $this->group('harvest_manage'),
                $this->group('harvest_due_full'),
                $this->group('harvest_requests_view'),
                $this->group('harvest_requests_review'),
                $this->group('harvest_requests_approve'),
                $this->group('harvest_requests_pay'),
                $this->group('harvest_requests_reject'),
                $this->group('payments_view'),
                $this->group('payments_maintain'),
                $this->group('new_payments'),
                $this->group('receivables_view'),
                $this->group('receivables_create'),
                $this->group('receivables_edit'),
                $this->group('receivables_delete'),
                $this->group('receivables_export'),
                $this->group('mobilizers_full'),
                $this->group('pos_full'),
                $this->group('expenses_view'),
                $this->group('expenses_manage'),
                $this->group('all_branches'),
                $this->group('reports'),
                $this->group('projects'),
                $this->group('projects_manage'),
                $this->group('project_categories'),
                $this->group('activities'),
                $this->group('products_view'),
                $this->group('products_manage')
            ),

            'head_records' => $this->mergePermissions(
                $this->group('dashboard'),
                $this->group('membership_view'),
                $this->group('membership_manage'),
                $this->group('contracts_view'),
                $this->group('contracts_create'),
                $this->group('contracts_edit'),
                $this->group('termination_view'),
                $this->group('harvest_view'),
                $this->group('harvest_due_full'),
                $this->group('payments_view'),
                $this->group('reports'),
                $this->group('projects'),
                $this->group('activities')
            ),

            'records_officer' => $this->mergePermissions(
                $this->group('dashboard'),
                $this->group('membership_view'),
                [
                    'members_register',
                    'create_members',
                ],
                $this->group('contracts_view'),
                $this->group('harvest_view'),
                $this->group('payments_view'),
                $this->group('projects'),
                $this->group('activities')
            ),

            'finance_lead' => $this->mergePermissions(
                $this->group('dashboard'),
                $this->group('notifications'),
                $this->group('membership_view'),
                $this->group('contracts_view'),
                $this->group('contracts_create'),
                $this->group('contracts_edit'),
                $this->group('contracts_delete'),
                $this->group('termination_view'),
                $this->group('harvest_view'),
                $this->group('harvest_requests_view'),
                $this->group('harvest_requests_pay'),
                $this->group('harvest_requests_reject'),
                $this->group('payments_view'),
                $this->group('payments_maintain'),
                $this->group('new_payments'),
                $this->group('receivables_view'),
                $this->group('receivables_create'),
                $this->group('receivables_edit'),
                $this->group('receivables_delete'),
                $this->group('receivables_export'),
                $this->group('pos_full'),
                $this->group('expenses_view'),
                $this->group('expenses_manage'),
                $this->group('all_branches'),
                $this->group('reports'),
                $this->group('products_view'),
                $this->group('products_manage'),
                $this->group('projects'),
                $this->group('projects_manage'),
                $this->group('project_categories')
            ),

            'accountant' => $this->mergePermissions(
                $this->group('dashboard'),
                $this->group('notifications'),
                $this->group('membership_view'),
                $this->group('contracts_view'),
                $this->group('contracts_create'),
                $this->group('contracts_delete'),
                $this->group('termination_view'),
                $this->group('harvest_view'),
                $this->group('harvest_requests_view'),
                $this->group('harvest_requests_pay'),
                $this->group('harvest_requests_reject'),
                $this->group('payments_view'),
                $this->group('payments_maintain'),
                $this->group('new_payments'),
                $this->group('receivables_view'),
                $this->group('receivables_create'),
                $this->group('receivables_edit'),
                $this->group('receivables_delete'),
                $this->group('receivables_export'),
                $this->group('pos_full'),
                $this->group('expenses_view'),
                $this->group('expenses_manage'),
                $this->group('all_branches'),
                $this->group('reports'),
                $this->group('products_view')
            ),

            'payment_harvest' => $this->mergePermissions(
                $this->group('dashboard'),
                ['members', 'members_view', 'view_members', 'search_members'],
                $this->group('contracts_view'),
                $this->group('termination_view'),
                $this->group('harvest_view'),
                $this->group('harvest_due_full'),
                $this->group('payments_view'),
                $this->group('payments_maintain'),
                $this->group('harvest_requests_view'),
                $this->group('harvest_requests_pay'),
                $this->group('harvest_requests_reject'),
                $this->group('reports')
            ),

            'head_procurement' => $this->mergePermissions(
                $this->group('dashboard'),
                $this->group('notifications'),
                $this->group('projects'),
                $this->group('harvest_view'),
                $this->group('contracts_view'),
                $this->group('activities'),
                $this->group('products_view'),
                $this->group('products_manage'),
                [
                    'stock_reports',
                    'stock_report',
                    'view_stock_report',
                    'export_stock_report',
                    'print_stock_report',
                    'expiry_stock',
                    'expiry_stock_report',
                    'view_expiry_stock',
                    'export_expiry_stock',
                    'low_stock_report',
                ]
            ),

            'assistant_procurement' => $this->mergePermissions(
                $this->group('dashboard'),
                $this->group('notifications'),
                $this->group('projects'),
                $this->group('harvest_view'),
                $this->group('activities'),
                $this->group('products_view'),
                $this->group('products_manage'),
                [
                    'stock_reports',
                    'stock_report',
                    'view_stock_report',
                    'export_stock_report',
                    'print_stock_report',
                    'expiry_stock',
                    'expiry_stock_report',
                    'view_expiry_stock',
                    'export_expiry_stock',
                    'low_stock_report',
                ]
            ),

            'member', 'customer' => $this->group('dashboard'),

            default => [],
        };
    }

    /* ============================================================
       Required / blocked permissions
    ============================================================ */

    public function requiredPermissions(string $role): array
    {
        $role = $this->normalizeRole($role);

        return match ($role) {
            'admin' => [
                'view_dashboard',
                'manage_users',
                'manage_permissions',
            ],

            'payment_harvest' => $this->mergePermissions(
                $this->group('dashboard'),
                [
                    'harvest_due',
                    'harvest_due_view',
                    'view_harvest_due',
                    'harvest_due_export',
                    'export_harvest_due',
                    'harvest_due_print',
                    'print_harvest_due',
                    'record_harvest',
                    'record_harvests',
                    'harvest_record',
                    'harvests_record',
                ]
            ),

            default => [],
        };
    }

    /**
     * Business permissions are intentionally NOT hard-blocked here.
     * The role-permissions screen is the source of truth for Admin and
     * Super Admin (see legacy auth_blocked_permissions()).
     */
    public function blockedPermissions(string $role): array
    {
        $this->normalizeRole($role);

        return [];
    }

    /* ============================================================
       DB-backed permissions (role_permissions table)
    ============================================================ */

    public function dbPermissions(string $role): array
    {
        $role = $this->normalizeRole($role);

        if ($role === '') {
            return [];
        }

        return RolePermission::query()
            ->where('role', $role)
            ->where('permission', '!=', self::CONFIG_MARKER)
            ->orderBy('permission')
            ->pluck('permission')
            ->map(fn ($permission) => $this->normalizePermission($permission))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function rolePermissionsCustomized(string $role): bool
    {
        $role = $this->normalizeRole($role);

        if ($role === '') {
            return false;
        }

        return RolePermission::query()
            ->where('role', $role)
            ->where('permission', self::CONFIG_MARKER)
            ->exists();
    }

    /**
     * Resolve the effective permission list for a role.
     *
     * Legacy roles without the "__configured__" marker keep the old
     * behaviour: database permissions extend built-in defaults. Once saved
     * from the role permissions screen, the database selection becomes
     * authoritative.
     */
    public function effectivePermissionsForRole(string $role): array
    {
        $role = $this->normalizeRole($role);

        if ($role === '') {
            return [];
        }

        if (array_key_exists($role, static::$effectiveCache)) {
            return static::$effectiveCache[$role];
        }

        if ($role === 'supper_admin') {
            return static::$effectiveCache[$role] = ['*'];
        }

        $database = $this->dbPermissions($role);
        $customized = $this->rolePermissionsCustomized($role);

        $base = $customized
            ? $database
            : $this->mergePermissions($this->defaultPermissions($role), $database);

        $permissions = $this->mergePermissions(
            $base,
            $this->requiredPermissions($role)
        );

        return static::$effectiveCache[$role] = array_values(array_diff(
            $permissions,
            $this->blockedPermissions($role)
        ));
    }

    /* ============================================================
       Sidebar navigation role map (port of nav_permission_role_map)
    ============================================================ */

    public function navPermissionRoleMap(): array
    {
        $dashboardRoles = $this->dashboardRoles();

        $adminOnly = [
            'supper_admin',
            'admin',
        ];

        $adminAndDirector = [
            'director',
            'supper_admin',
            'admin',
        ];

        $allManagement = $this->managementRoles();

        return [
            'view_dashboard' => $dashboardRoles,

            'manage_ypa' => $adminAndDirector,
            'manage_orders' => $adminAndDirector,
            'manage_products' => ['director', 'manager', 'supper_admin', 'admin', 'head_procurement', 'assistant_procurement'],
            'manage_stock' => ['director', 'manager', 'supper_admin', 'admin', 'head_procurement', 'assistant_procurement'],
            'manage_customers' => ['director', 'manager', 'supper_admin', 'admin', 'finance_lead', 'accountant'],
            'manage_suppliers' => ['director', 'manager', 'supper_admin', 'admin', 'head_procurement', 'assistant_procurement'],
            'manage_branches' => ['supper_admin'],
            'manage_sales' => [
                'director',
                'supper_admin',
                'admin',
                'finance_lead',
                'accountant',
            ],
            'manage_expenses' => ['director', 'manager', 'supper_admin', 'admin', 'finance_lead', 'accountant'],

            'pos' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'point_of_sale' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'sales' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'sales_view' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'view_sales' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'sales_create' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'create_sales' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'sales_edit' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'edit_sales' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'sales_export' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'export_sales' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'invoice_print' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'print_invoice' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'receipt_print' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],
            'print_receipt' => ['supper_admin', 'admin', 'finance_lead', 'accountant'],

            'view_business_report' => ['director', 'manager', 'supper_admin', 'finance_lead', 'accountant'],
            'view_sales_report' => ['director', 'manager', 'supper_admin', 'finance_lead', 'accountant'],
            'view_stock_report' => ['director', 'manager', 'supper_admin', 'finance_lead', 'accountant', 'head_procurement', 'assistant_procurement'],
            'view_low_stock_report' => ['director', 'manager', 'supper_admin', 'head_procurement', 'assistant_procurement'],
            'view_expiry_stock_report' => ['head_procurement', 'assistant_procurement'],

            'notifications' => $allManagement,
            'notifications_low_stock' => ['head_procurement', 'assistant_procurement'],
            'notifications_expiry_stock' => ['head_procurement', 'assistant_procurement'],

            'manage_membership' => $allManagement,

            'contracts' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'records_officer', 'finance_lead', 'accountant', 'payment_harvest'],
            'contracts_create' => ['supper_admin', 'finance_lead', 'accountant', 'payment_harvest'],
            'contracts_export' => ['supper_admin', 'admin', 'head_records', 'records_officer', 'finance_lead', 'accountant'],
            'contracts_payments' => ['finance_lead', 'accountant'],
            'contracts_edit' => ['supper_admin', 'finance_lead'],

            'projects' => ['supper_admin', 'finance_lead'],

            'harvests' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'records_officer', 'finance_lead', 'payment_harvest'],
            'harvest_due' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'records_officer', 'finance_lead', 'payment_harvest'],

            'termination' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'records_officer', 'finance_lead', 'accountant', 'payment_harvest'],
            'receivables' => ['supper_admin', 'finance_lead', 'accountant'],
            'payments' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'records_officer', 'finance_lead', 'accountant', 'payment_harvest'],

            'members' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'records_officer', 'finance_lead', 'accountant'],
            'groups' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'records_officer'],
            'mobilizers' => ['supper_admin', 'admin'],
            'meetings' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'records_officer'],
            'activities' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'records_officer'],
            'financial_reports' => ['director', 'manager', 'supper_admin', 'admin', 'head_records', 'finance_lead', 'accountant'],

            'manage_users' => $adminOnly,
            'manage_permissions' => $adminOnly,
            'manage_settings' => $adminOnly,
        ];
    }

    /* ============================================================
       Checks
    ============================================================ */

    public function roleAllowed(string|array $roles, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        $current = $this->normalizeRole($user->role);

        $allowed = array_map(
            fn ($role) => $this->normalizeRole($role),
            (array) $roles
        );

        return in_array($current, $allowed, true);
    }

    public function can(string|array $permissions, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        $role = $this->normalizeRole($user->role);

        if ($role === '') {
            return false;
        }

        $requested = [];

        foreach ((array) $permissions as $permission) {
            $permission = $this->normalizePermission($permission);
            if ($permission !== '') {
                $requested[] = $permission;

                // Middleware may use a permission-group name (for example,
                // harvest_manage) while role defaults store the individual
                // permissions in that group.
                if (array_key_exists($permission, $this->permissionGroups())) {
                    $requested = array_merge($requested, $this->group($permission));
                }
            }
        }

        if (!$requested) {
            return false;
        }

        $effective = $this->effectivePermissionsForRole($role);

        if (in_array('*', $effective, true)) {
            return true;
        }

        foreach ($requested as $permission) {
            if (in_array($permission, $effective, true)) {
                return true;
            }
        }

        return false;
    }

    public function clearEffectiveCache(?string $role = null): void
    {
        if ($role === null) {
            static::$effectiveCache = [];

            return;
        }

        unset(static::$effectiveCache[$this->normalizeRole($role)]);
    }

    public function canAny(string|array $permissions, ?User $user = null): bool
    {
        return $this->can($permissions, $user);
    }

    public function canAll(string|array $permissions, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        foreach ((array) $permissions as $permission) {
            if (!$this->can((string) $permission, $user)) {
                return false;
            }
        }

        return true;
    }

    /* ============================================================
       Labels / URLs
    ============================================================ */

    public function roleLabel(mixed $role): string
    {
        $role = $this->normalizeRole($role);

        return match ($role) {
            'director' => 'Director',
            'manager' => 'Manager',
            'supper_admin' => 'Super Admin',
            'admin' => 'Admin',
            'head_records' => 'Head Records',
            'records_officer' => 'Records Officer',
            'finance_lead' => 'Finance Lead',
            'accountant' => 'Accountant',
            'payment_harvest' => 'Payment Harvest',
            'head_procurement' => 'Head Procurement',
            'assistant_procurement' => 'Assistant Procurement',
            'member' => 'Member',
            'customer' => 'Customer',
            default => ucwords(str_replace('_', ' ', $role)),
        };
    }

    public function roleHierarchy(): array
    {
        return [
            'supper_admin' => 100,
            'director' => 90,
            'admin' => 80,
            'manager' => 70,
            'finance_lead' => 60,
            'accountant' => 55,
            'head_records' => 50,
            'records_officer' => 45,
            'head_procurement' => 40,
            'assistant_procurement' => 35,
            'payment_harvest' => 30,
            'member' => 10,
            'customer' => 5,
        ];
    }

    public function roleLevel(string $role): int
    {
        $role = $this->normalizeRole($role);
        return $this->roleHierarchy()[$role] ?? 0;
    }

    public function canManageRole(string $targetRole, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }
        $currentLevel = $this->roleLevel($user->role);
        $targetLevel = $this->roleLevel($targetRole);
        return $currentLevel > $targetLevel;
    }

    public function isHighestPrivileged(string $role): bool
    {
        return $this->normalizeRole($role) === 'supper_admin';
    }

    /**
     * Route name for the role's landing page (legacy nav_home_url()).
     */
    public function homeUrl(string $role): string
    {
        return match ($this->normalizeRole($role)) {
            'director',
            'manager',
            'supper_admin',
            'admin',
            'head_records',
            'records_officer',
            'finance_lead',
            'accountant',
            'payment_harvest' => 'dashboard',

            'head_procurement',
            'assistant_procurement' => 'projects.index',

            'member' => 'member.dashboard',
            'customer' => 'customer.dashboard',

            default => 'logout',
        };
    }
}
