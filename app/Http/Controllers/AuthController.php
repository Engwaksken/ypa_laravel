<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Public auth pages (login, verify, reset) — no auth middleware in the
     * legacy flow. Logout is safe for guests (Auth::logout() no-ops).
     */
    public static function middleware(): array
    {
        return [];
    }

    /**
     * GET /login
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * POST /login
     *
     * Legacy flow: email + password -> 6-digit OTP (10-minute expiry) ->
     * email the code -> verify page.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if (!$user || !Hash::check($request->validated('password'), $user->password)) {
            return back()
                ->withErrors(['email' => 'Invalid email or password. Please try again.'])
                ->withInput($request->only('email'));
        }

        $code = sprintf('%06d', random_int(0, 999999));

        $user->forceFill([
            'verification_code' => $code,
            'code_expires' => now()->addMinutes(10),
        ])->save();

        if (!$this->sendVerificationEmail($user, $code)) {
            return back()->withErrors(['email' => 'Failed to send verification code. Please try again.']);
        }

        session([
            'verify_user_id' => $user->id,
            'verify_email' => $user->email,
        ]);

        return redirect()->route('verify');
    }

    /**
     * GET /verify
     */
    public function showVerify(): View|RedirectResponse
    {
        if (!session('verify_user_id') || !session('verify_email')) {
            return redirect()->route('login');
        }

        return view('auth.verify', ['email' => session('verify_email')]);
    }

    /**
     * POST /verify
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = (int) session('verify_user_id');

        if ($userId <= 0) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->code_expires && $user->code_expires->lt(now())) {
            return back()->withErrors(['code' => 'Verification code has expired. Please request a new one.']);
        }

        if ($user->verification_code !== $request->input('code')) {
            return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }

        $user->forceFill([
            'verification_code' => null,
            'code_expires' => null,
        ])->save();

        Auth::login($user);

        session()->forget(['verify_user_id', 'verify_email']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * POST /verify/resend
     */
    public function resend(Request $request): RedirectResponse
    {
        $userId = (int) session('verify_user_id');

        if ($userId <= 0) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $code = sprintf('%06d', random_int(0, 999999));

        $user->forceFill([
            'verification_code' => $code,
            'code_expires' => now()->addMinutes(10),
        ])->save();

        if (!$this->sendVerificationEmail($user, $code)) {
            return back()->withErrors(['code' => 'Failed to send verification code. Please try again.']);
        }

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    /**
     * POST /logout
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * GET /reset
     */
    public function showReset(): View
    {
        return view('auth.reset');
    }

    /**
     * POST /reset
     *
     * Sends the password reset email when the address exists. The full
     * token-based reset flow is Phase 3; this wires the request form and
     * the legacy email template now.
     */
    public function sendReset(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $request->input('email'))->first();

        if ($user) {
            $this->sendPasswordResetEmail($user);
        }

        return back()->with('success', 'If that email address exists, a password reset link has been sent.');
    }

    protected function sendVerificationEmail(User $user, string $code): bool
    {
        try {
            $html = view('emails.verification-code', [
                'name' => $user->name ?? 'User',
                'code' => $code,
            ])->render();

            Mail::html($html, function ($message) use ($user) {
                $message->to($user->email, $user->name ?? 'User')
                    ->subject('Your Verification Code - ' . config('app.name'));
            });

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    protected function sendPasswordResetEmail(User $user): void
    {
        try {
            $html = view('emails.password-reset', [
                'name' => $user->name ?? 'User',
                'resetLink' => route('password.request'),
            ])->render();

            Mail::html($html, function ($message) use ($user) {
                $message->to($user->email, $user->name ?? 'User')
                    ->subject('Password Reset Request - ' . config('app.name'));
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }
}