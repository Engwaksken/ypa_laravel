<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityParticipantController;
use App\Http\Controllers\ActivityRegistrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractPdfController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\ContractWorkflowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HarvestController;
use App\Http\Controllers\HarvestDueController;
use App\Http\Controllers\MeetingAjaxController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberProfileController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MobilizerController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\GuestOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TerminationController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
 * Laravel's `guest` middleware redirects authenticated users to the
 * `home` route (falling back to `/` when it is missing). Without this
 * route an authenticated user hitting /login bounced / -> login -> /
 * forever. `/home` breaks the loop by landing on the dashboard.
 */
Route::get('/home', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::get('/place-order', [GuestOrderController::class, 'create'])->name('place-order');
Route::post('/place-order', [GuestOrderController::class, 'store'])->name('place-order.store');

/* ============================================================
   Authentication (OTP flow)
   ============================================================ */

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt')->middleware('throttle:10,1');

    Route::get('/verify', [AuthController::class, 'showVerify'])->name('verify');
    Route::post('/verify', [AuthController::class, 'verify'])->name('verify.attempt')->middleware('throttle:5,1');
    Route::post('/verify/resend', [AuthController::class, 'resend'])->name('verify.resend')->middleware('throttle:5,1');

    Route::get('/reset', [AuthController::class, 'showReset'])->name('password.request');
    Route::post('/reset', [AuthController::class, 'sendReset'])->name('password.email')->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ============================================================
   Dashboard
   ============================================================ */

Route::middleware(['auth', 'user.status'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

/* ============================================================
   Members module (Phase 3 Batch 1a)
   ============================================================ */

Route::middleware(['auth', 'user.status'])->group(function () {
    // Members (admin/management CRUD + export + AJAX details).
    Route::get('/members/export', [MemberController::class, 'export'])->name('members.export')->middleware('throttle:10,1');
    Route::get('/members/ajax/details', [MemberController::class, 'getMemberDetails'])->name('members.ajax.details')->middleware('permission:members');
    Route::resource('members', MemberController::class);

    // Mobilizers (admin/management CRUD + AJAX get).
    Route::get('/mobilizers/ajax/get', [MobilizerController::class, 'get'])->name('mobilizers.ajax.get')->middleware('permission:mobilizers');
    Route::resource('mobilizers', MobilizerController::class);

    // Member self-service (role 'member').
    Route::get('/member/dashboard', [MemberProfileController::class, 'dashboard'])->name('member.dashboard');
    Route::get('/member/profile', [MemberProfileController::class, 'profile'])->name('member.profile');
    Route::post('/member/profile', [MemberProfileController::class, 'updateProfile'])->name('member.profile.update');
    Route::get('/member/next-of-kin', [MemberProfileController::class, 'nextOfKin'])->name('member.next_of_kin');
    Route::post('/member/next-of-kin', [MemberProfileController::class, 'saveNextOfKin'])->name('member.next_of_kin.save');
    Route::get('/member/bank-details', [MemberProfileController::class, 'bankDetails'])->name('member.bank_details');
    Route::post('/member/bank-details', [MemberProfileController::class, 'saveBankDetails'])->name('member.bank_details.save');

    // Membership dashboard.
    Route::get('/membership', [MembershipController::class, 'index'])->name('membership');
});

/* ============================================================
   Groups module (Phase 3 Batch 1b)
   ============================================================ */

Route::middleware(['auth', 'user.status'])->group(function () {
    // Groups (admin/management CRUD + export + AJAX details).
    Route::get('/groups/export', [GroupController::class, 'export'])->name('groups.export')->middleware('throttle:10,1');
    Route::get('/groups/ajax/details', [GroupController::class, 'getGroupDetails'])
        ->name('groups.ajax.details')
        ->middleware('permission:groups');
    Route::resource('groups', GroupController::class);
});

/* ============================================================
   Activities module (Phase 3 Batch 1b)
   ============================================================ */

Route::middleware(['auth', 'user.status'])->group(function () {
    // Activities (admin/management CRUD + export + AJAX details).
    Route::get('/activities/export', [ActivityController::class, 'export'])->name('activities.export')->middleware('throttle:10,1');
    Route::get('/activities/ajax/details', [ActivityController::class, 'getActivityDetails'])
        ->name('activities.ajax.details')
        ->middleware('permission:activities');
    Route::resource('activities', ActivityController::class);

    // Activity participants (admin/management).
    Route::get('/activities/{activity}/participants', [ActivityParticipantController::class, 'index'])->name('activities.participants');
    Route::post('/activities/{activity}/participants', [ActivityParticipantController::class, 'store'])->name('activities.participants.store')->middleware('throttle:30,1');
    Route::post('/activities/{activity}/participants/{participant}/attendance', [ActivityParticipantController::class, 'updateAttendance'])->name('activities.participants.attendance')->middleware('throttle:30,1');
    Route::delete('/activities/{activity}/participants/{participant}', [ActivityParticipantController::class, 'destroy'])->name('activities.participants.destroy');
    Route::get('/activities/ajax/search-members', [ActivityParticipantController::class, 'searchMembers'])
        ->name('activities.ajax.search_members')
        ->middleware(['permission:activities_edit', 'throttle:30,1']);
});

/* ============================================================
   Meetings module (Phase 3 Batch 1b)
   ============================================================ */

Route::middleware(['auth', 'user.status'])->group(function () {
    // Meetings (admin/management CRUD).
    Route::resource('meetings', MeetingController::class);

    // Meetings AJAX (mirrors meetings_ajax.php; admin/manager only).
    Route::get('/meetings/ajax/get-invites', [MeetingAjaxController::class, 'getInvites'])->name('meetings.ajax.get_invites');
    Route::post('/meetings/ajax/save-invites', [MeetingAjaxController::class, 'saveInvites'])->name('meetings.ajax.save_invites')->middleware('throttle:30,1');
    Route::get('/meetings/ajax/get-attendance', [MeetingAjaxController::class, 'getAttendance'])->name('meetings.ajax.get_attendance');
    Route::post('/meetings/ajax/save-attendance', [MeetingAjaxController::class, 'saveAttendance'])->name('meetings.ajax.save_attendance')->middleware('throttle:30,1');
    Route::post('/meetings/ajax/mark-complete', [MeetingAjaxController::class, 'markComplete'])->name('meetings.ajax.mark_complete')->middleware('throttle:30,1');
    Route::get('/meetings/ajax/search-members', [MeetingAjaxController::class, 'searchMembers'])
        ->name('meetings.ajax.search_members')
        ->middleware('throttle:30,1');
});

/* ============================================================
   Contracts module (Phase 3 Batch 2)
   ============================================================ */

Route::middleware(['auth', 'user.status'])->group(function () {
    // Harvest Due and Harvest Management.
    Route::get('/harvest-due', [HarvestDueController::class, 'index'])->name('harvest-due.index')->middleware('permission:harvest_due');
    Route::post('/harvest-due/{contractItem}/record', [HarvestDueController::class, 'record'])->name('harvest-due.record')->middleware(['permission:harvest_due_full', 'throttle:30,1']);
    Route::get('/harvests/export', [HarvestController::class, 'export'])->name('harvests.export')->middleware(['permission:harvest_view', 'throttle:10,1']);
    Route::post('/harvests/{harvest}/review', [HarvestController::class, 'review'])->name('harvests.review')->middleware(['permission:harvest_requests_review', 'throttle:30,1']);
    Route::post('/harvests/{harvest}/approve', [HarvestController::class, 'approve'])->name('harvests.approve')->middleware(['permission:harvest_requests_approve', 'throttle:30,1']);
    Route::post('/harvests/{harvest}/reject', [HarvestController::class, 'reject'])->name('harvests.reject')->middleware(['permission:harvest_requests_reject', 'throttle:30,1']);
    Route::post('/harvests/{harvest}/pay', [HarvestController::class, 'pay'])->name('harvests.pay')->middleware(['permission:harvest_requests_pay', 'throttle:30,1']);
    Route::resource('harvests', HarvestController::class)->except(['edit', 'update']);

    // Payments (contract payment ledger / creation).
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index')->middleware('permission:payments_view');
    Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create')->middleware('permission:new_payments');
    Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export')->middleware(['permission:payments_export', 'throttle:10,1']);
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store')->middleware(['permission:new_payments', 'throttle:30,1']);
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve')->middleware(['permission:payments_maintain', 'throttle:30,1']);
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject')->middleware(['permission:payments_maintain', 'throttle:30,1']);
    Route::post('/payments/{payment}/reconcile', [PaymentController::class, 'reconcile'])->name('payments.reconcile')->middleware(['permission:payments_maintain', 'throttle:30,1']);
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt')->middleware('permission:payments_view');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show')->middleware('permission:payments_view');

    // Receivables (income receivables + payments).
    Route::get('/receivables/export', [ReceivableController::class, 'export'])->name('receivables.export')->middleware(['permission:receivables_export', 'throttle:10,1']);
    Route::post('/receivables/{receivable}/payments', [ReceivableController::class, 'pay'])->name('receivables.pay')->middleware(['permission:receivables_create', 'throttle:30,1']);
    Route::resource('receivables', ReceivableController::class);

    // Contract terminations.
    Route::get('/termination', [TerminationController::class, 'index'])->name('termination.index')->middleware('permission:termination');
    Route::get('/termination/create', [TerminationController::class, 'create'])->name('termination.create')->middleware('permission:termination');
    Route::post('/termination', [TerminationController::class, 'store'])->name('termination.store')->middleware(['permission:termination', 'throttle:30,1']);
    Route::get('/termination/{termination}', [TerminationController::class, 'show'])->name('termination.show')->middleware('permission:termination');

    Route::get('/contracts/export', [ContractController::class, 'export'])
        ->name('contracts.export')
        ->middleware(['permission:contracts_view', 'throttle:10,1']);
    Route::get('/contracts/{contract}/pdf', [ContractPdfController::class, 'show'])
        ->name('contracts.pdf')
        ->middleware('permission:contracts_view');

    Route::post('/contracts/{contract}/workflow/save-draft', [ContractWorkflowController::class, 'saveDraft'])
        ->name('contracts.workflow.save_draft')
        ->middleware(['permission:contracts_edit', 'throttle:30,1']);
    Route::post('/contracts/{contract}/workflow/submit', [ContractWorkflowController::class, 'submit'])
        ->name('contracts.workflow.submit')
        ->middleware(['permission:contracts_edit', 'throttle:30,1']);
    Route::post('/contracts/{contract}/workflow/sign', [ContractWorkflowController::class, 'sign'])
        ->name('contracts.workflow.sign')
        ->middleware(['permission:contracts_edit', 'throttle:30,1']);
    Route::post('/contracts/{contract}/workflow/reject', [ContractWorkflowController::class, 'reject'])
        ->name('contracts.workflow.reject')
        ->middleware(['permission:contracts_edit', 'throttle:30,1']);
    Route::post('/contracts/{contract}/workflow/finalize', [ContractWorkflowController::class, 'finalize'])
        ->name('contracts.workflow.finalize')
        ->middleware(['permission:contracts_edit', 'throttle:30,1']);

    Route::get('/contract-templates/{contractTemplate}/preview', [ContractTemplateController::class, 'preview'])
        ->name('contract-templates.preview')
        ->middleware('permission:contracts_view');
    Route::resource('contract-templates', ContractTemplateController::class)
        ->parameters(['contract-templates' => 'contractTemplate']);

    Route::resource('contracts', ContractController::class);
});

/* ============================================================
   Administration: settings, users, permissions, notifications
   ============================================================ */

Route::middleware(['auth', 'user.status'])->group(function () {
    // Settings (site identity + branding).
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Users (admin CRUD).
    Route::resource('users', UsersController::class);

    Route::resource('branches', BranchController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
    Route::resource('stock', StockController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

    // Projects module (Operations).
    Route::get('/projects/export', [ProjectController::class, 'export'])->name('projects.export')->middleware('throttle:10,1');
    Route::get('/projects/ajax/details', [ProjectController::class, 'getProjectDetails'])
        ->name('projects.ajax.details')
        ->middleware('permission:projects');
    Route::resource('projects', ProjectController::class)->except(['create', 'edit', 'show']);

    // Project categories (admin/manager/director reference data).
    Route::get('/project-categories/export', [ProjectCategoryController::class, 'export'])->name('project-categories.export')->middleware('throttle:10,1');
    Route::get('/project-categories/{projectCategory}/edit-data', [ProjectCategoryController::class, 'getCategoryDetails'])->name('project-categories.edit-data');
    Route::resource('project-categories', ProjectCategoryController::class)->only(['index', 'store', 'update', 'destroy']);

    // Permissions (role matrix).
    Route::get('/permissions', [PermissionsController::class, 'index'])->name('permissions.index');
    Route::put('/permissions', [PermissionsController::class, 'update'])->name('permissions.update');

    // Notifications.
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationsController::class, 'destroy'])->name('notifications.destroy');
});

/* ============================================================
   Public activity registration (no auth — legacy behaviour)
   ============================================================ */

Route::get('/activity-registration', [ActivityRegistrationController::class, 'show'])->name('activity-registration');
Route::post('/activity-registration', [ActivityRegistrationController::class, 'register'])
    ->name('activity-registration.register')
    ->middleware('throttle:20,1');
