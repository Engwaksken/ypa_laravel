<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberBankDetail;
use App\Models\MemberNextOfKin;
use App\Services\MemberService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Member self-service pages (legacy member-dashboard.php, member-profile.php,
 * next_of_kin.php, bank_details.php). Only accessible to the 'member' role
 * and scoped to the member record linked to the authenticated user.
 */
class MemberProfileController extends Controller
{
    protected PermissionService $permission;

    protected MemberService $memberService;

    public function __construct()
    {
        $this->permission = app(PermissionService::class);
        $this->memberService = app(MemberService::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
        ];
    }

    public function dashboard(): View|RedirectResponse
    {
        $member = $this->currentMember();
        if (!$member) {
            return redirect()->route('dashboard')->with('error', 'Member profile not found. Please contact support.');
        }

        $bank = $member->bankDetail;
        $nok = $member->nextOfKin()->orderByDesc('id')->first();

        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        return view('member.dashboard', compact('member', 'bank', 'nok', 'greeting'));
    }

    public function profile(): View|RedirectResponse
    {
        $member = $this->currentMember();
        if (!$member) {
            return redirect()->route('member.dashboard')->with('error', 'Member profile not found.');
        }

        return view('member.profile', compact('member'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $member = $this->currentMember();
        if (!$member) {
            return redirect()->route('member.dashboard')->with('error', 'Member profile not found.');
        }

        $data = $request->validate([
            'email' => ['required', 'email'],
            'telephone1' => ['required', 'string'],
            'telephone2' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'nationality' => ['required', 'string'],
            'region' => ['nullable', Rule::in(MemberService::REGIONS)],
            'district_residence' => ['nullable', 'string'],
            'district' => ['nullable', 'string'],
            'employment_status' => ['required', Rule::in(MemberService::EMPLOYMENT_STATUSES)],
            'employment_other' => ['nullable', 'string'],
            'marital_status' => ['required', Rule::in(MemberService::MARITAL_STATUSES)],
            'children_count' => ['nullable', 'integer', 'min:0'],
            'source' => ['required', Rule::in(MemberService::SOURCES)],
            'source_other' => ['nullable', 'string'],
            'mother_name' => ['nullable', 'string'],
            'mother_phone' => ['nullable', 'string'],
            'father_name' => ['nullable', 'string'],
            'father_phone' => ['nullable', 'string'],
            'account_type' => ['required', Rule::in(MemberService::ACCOUNT_TYPES)],
        ]);

        // Phone validation.
        foreach (['telephone1' => 'Telephone 1', 'telephone2' => 'Telephone 2', 'mother_phone' => 'Mother phone', 'father_phone' => 'Father phone'] as $field => $label) {
            $value = (string) ($data[$field] ?? '');
            $required = $field === 'telephone1';
            if (!$this->memberService->validPhone($value, $required)) {
                if ($required || $value !== '') {
                    return back()->withErrors([$field => "{$label} must start with country code, for example +256, and must be exactly 13 characters including +."])->withInput();
                }
            }
        }

        // Employment "Other" rule.
        if (($data['employment_status'] ?? '') === 'Other' && trim((string) ($data['employment_other'] ?? '')) === '') {
            return back()->withErrors(['employment_other' => 'Please specify Employment Status (Other).'])->withInput();
        }

        // Source "Other" rule.
        if (($data['source'] ?? '') === 'Other' && trim((string) ($data['source_other'] ?? '')) === '') {
            return back()->withErrors(['source_other' => 'Please specify How did you know about us (Other).'])->withInput();
        }

        // Uganda rule.
        $isUganda = strtolower(trim((string) ($data['nationality'] ?? ''))) === 'uganda';
        if ($isUganda) {
            if (trim((string) ($data['address'] ?? '')) === '') {
                return back()->withErrors(['address' => 'Address/Residence is required for Ugandan nationals.'])->withInput();
            }
            if (trim((string) ($data['region'] ?? '')) === '') {
                return back()->withErrors(['region' => 'Region is required for Ugandan nationals.'])->withInput();
            }
            if (trim((string) ($data['district_residence'] ?? '')) === '') {
                return back()->withErrors(['district_residence' => 'District of Residence is required for Ugandan nationals.'])->withInput();
            }
        }

        $member->update([
            'email' => $this->memberService->nullIfEmpty((string) ($data['email'] ?? '')),
            'telephone1' => $this->memberService->normalizePhone((string) ($data['telephone1'] ?? '')),
            'telephone2' => $this->memberService->normalizePhone((string) ($data['telephone2'] ?? '')),
            'address' => $this->memberService->nullIfEmpty((string) ($data['address'] ?? '')),
            'nationality' => $this->memberService->nullIfEmpty((string) ($data['nationality'] ?? '')),
            'region' => $this->memberService->nullIfEmpty((string) ($data['region'] ?? '')),
            'district_residence' => $this->memberService->nullIfEmpty((string) ($data['district_residence'] ?? '')),
            'district' => $this->memberService->nullIfEmpty((string) ($data['district'] ?? '')),
            'employment_status' => $this->memberService->employmentValue((string) ($data['employment_status'] ?? ''), (string) ($data['employment_other'] ?? '')),
            'employment_other' => $this->memberService->nullIfEmpty((string) ($data['employment_other'] ?? '')),
            'marital_status' => $this->memberService->nullIfEmpty((string) ($data['marital_status'] ?? '')),
            'children_count' => max(0, (int) ($data['children_count'] ?? 0)),
            'source' => $this->memberService->sourceValue((string) ($data['source'] ?? ''), (string) ($data['source_other'] ?? '')),
            'source_other' => $this->memberService->nullIfEmpty((string) ($data['source_other'] ?? '')),
            'mother_name' => $this->memberService->nullIfEmpty((string) ($data['mother_name'] ?? '')),
            'mother_phone' => $this->memberService->normalizePhone((string) ($data['mother_phone'] ?? '')),
            'father_name' => $this->memberService->nullIfEmpty((string) ($data['father_name'] ?? '')),
            'father_phone' => $this->memberService->normalizePhone((string) ($data['father_phone'] ?? '')),
            'account_type' => $this->memberService->nullIfEmpty((string) ($data['account_type'] ?? '')),
        ]);

        return redirect()->route('member.profile')->with('success', 'Profile updated successfully.');
    }

    public function nextOfKin(): View|RedirectResponse
    {
        $member = $this->currentMember();
        if (!$member) {
            return redirect()->route('member.dashboard')->with('error', 'Member profile not found.');
        }

        $nok = $member->nextOfKin()->orderByDesc('id')->first();

        return view('member.next_of_kin', compact('member', 'nok'));
    }

    public function saveNextOfKin(Request $request): RedirectResponse
    {
        $member = $this->currentMember();
        if (!$member) {
            return redirect()->route('member.dashboard')->with('error', 'Member profile not found.');
        }

        $data = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'nin' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'relationship' => ['required', 'string'],
            'address' => ['nullable', 'string'],
            'phone' => ['required', 'string'],
            'email' => ['nullable', 'email'],
        ]);

        $phone = $this->memberService->normalizePhone((string) ($data['phone'] ?? ''));
        if (!$this->memberService->validPhone($phone, true)) {
            return back()->withErrors(['phone' => 'Phone must start with country code, for example +256, and must be exactly 13 characters including +.'])->withInput();
        }

        $nok = $member->nextOfKin()->orderByDesc('id')->first();

        $values = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'nin' => $this->memberService->nullIfEmpty((string) ($data['nin'] ?? '')),
            'date_of_birth' => $this->memberService->nullIfEmpty((string) ($data['date_of_birth'] ?? '')),
            'relationship' => $data['relationship'],
            'address' => $this->memberService->nullIfEmpty((string) ($data['address'] ?? '')),
            'phone' => $phone,
            'email' => $this->memberService->nullIfEmpty((string) ($data['email'] ?? '')),
        ];

        if ($nok) {
            $nok->update($values);
        } else {
            $member->nextOfKin()->create($values);
        }

        return redirect()->route('member.next_of_kin')->with('success', 'Next of kin details saved successfully.');
    }

    public function bankDetails(): View|RedirectResponse
    {
        $member = $this->currentMember();
        if (!$member) {
            return redirect()->route('member.dashboard')->with('error', 'Member profile not found.');
        }

        $bank = $member->bankDetail;

        return view('member.bank_details', compact('member', 'bank'));
    }

    public function saveBankDetails(Request $request): RedirectResponse
    {
        $member = $this->currentMember();
        if (!$member) {
            return redirect()->route('member.dashboard')->with('error', 'Member profile not found.');
        }

        $data = $request->validate([
            'bank_account' => ['nullable', 'string'],
            'account_name' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'bank_branch' => ['nullable', 'string'],
        ]);

        $bankAccount = trim((string) ($data['bank_account'] ?? ''));
        $accountName = trim((string) ($data['account_name'] ?? ''));
        $bankName = trim((string) ($data['bank_name'] ?? ''));
        $bankBranch = trim((string) ($data['bank_branch'] ?? ''));

        if ($bankAccount === '' && $accountName === '' && $bankName === '' && $bankBranch === '') {
            return back()->withErrors(['bank_account' => 'Please fill at least one bank detail field.'])->withInput();
        }

        // Upsert (member_bank_details has UNIQUE(member_id)).
        MemberBankDetail::updateOrCreate(
            ['member_id' => $member->id],
            [
                'bank_account' => $this->memberService->nullIfEmpty($bankAccount),
                'account_name' => $this->memberService->nullIfEmpty($accountName),
                'bank_name' => $this->memberService->nullIfEmpty($bankName),
                'bank_branch' => $this->memberService->nullIfEmpty($bankBranch),
            ]
        );

        return redirect()->route('member.bank_details')->with('success', 'Bank details saved successfully.');
    }

    /**
     * Resolve the member record linked to the authenticated user.
     */
    protected function currentMember(): ?Member
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        return Member::query()->where('user_id', $user->id)->first();
    }
}
