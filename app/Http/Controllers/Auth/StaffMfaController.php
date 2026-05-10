<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\StaffMfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffMfaController extends Controller
{
    public function __construct(
        protected StaffMfaService $mfa,
        protected AuditLogService $auditLogs
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->resolvePendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.mfa-challenge', [
            'email' => $this->maskEmail((string) $user->email),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->resolvePendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'digits:'.max(4, (int) config('security.mfa.code_length', 6))],
        ]);

        $attemptKey = 'mfa-attempt:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Too many verification attempts. Please request a new code and try again later.',
            ]);
        }

        try {
            $this->mfa->verifyChallenge($user, (string) $request->input('code'));
        } catch (ValidationException $exception) {
            RateLimiter::hit($attemptKey, 600);
            $this->auditLogs->log($request, 'auth.mfa_failed', $user, ['reason' => 'invalid_code'], $user);
            throw $exception;
        }

        RateLimiter::clear($attemptKey);
        $this->mfa->clearChallenge($user);

        if ($request->session()->pull('auth.pending_mfa_remember', false)) {
            $this->mfa->rememberDevice($user);
        }

        Auth::login($user, false);
        $request->session()->forget('auth.pending_mfa_user_id');
        $request->session()->regenerate();

        $this->auditLogs->log($request, 'auth.mfa_verified', $user, [], $user);

        return $this->redirectForRole($user->role);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->resolvePendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $key = 'mfa-resend:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'code' => 'Too many resend attempts. Please wait a few minutes before requesting another code.',
            ]);
        }

        RateLimiter::hit($key, 300);
        $this->mfa->issueChallenge($user);
        $this->auditLogs->log($request, 'auth.mfa_resent', $user, [], $user);

        return back()->with('status', 'A new verification code was sent to your email.');
    }

    protected function resolvePendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('auth.pending_mfa_user_id');

        return $userId ? User::find($userId) : null;
    }

    protected function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $local = strlen($local) <= 2 ? str_repeat('*', strlen($local)) : substr($local, 0, 2).str_repeat('*', max(strlen($local) - 2, 1));

        return $local.'@'.$domain;
    }

    protected function redirectForRole(string $role): RedirectResponse
    {
        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'reporting_officer' => redirect()->route('reporting.dashboard'),
            'social_worker' => redirect('/social-worker/dashboard'),
            'approving_officer' => redirect()->route('approving.dashboard'),
            'service_provider' => redirect()->route('service-provider.dashboard'),
            'referral_institution' => redirect()->route('referral-institution.dashboard'),
            'referral_officer' => redirect()->route('referral-officer.dashboard'),
            default => redirect()->route('client.dashboard'),
        };
    }
}
