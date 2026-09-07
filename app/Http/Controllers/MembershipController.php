<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Membership dashboard (legacy membership.php). Shows the authenticated
 * member's membership status, payment history and related information.
 */
class MembershipController extends Controller
{
    protected PermissionService $permission;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
        ];
    }

    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        $member = Member::query()
            ->with(['bankDetail', 'nextOfKin', 'mobilizations.mobilizer'])
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return redirect()->route('member.dashboard')->with('error', 'Member profile not found. Please contact support.');
        }

        $bank = $member->bankDetail;
        $nok = $member->nextOfKin()->orderByDesc('id')->first();

        // Payment history (only if the payment_transactions table exists).
        $payments = collect();
        $hasPaymentTable = \Schema::hasTable('payment_transactions');
        if ($hasPaymentTable) {
            $payments = \DB::table('payment_transactions')
                ->where('member_id', $member->id)
                ->orderByDesc('created_at')
                ->get();
        }

        return view('member.membership', compact('member', 'bank', 'nok', 'payments', 'hasPaymentTable'));
    }
}
