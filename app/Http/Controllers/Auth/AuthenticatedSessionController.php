<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogService;
use App\Services\StaffMfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected StaffMfaService $mfa,
        protected AuditLogService $auditLogs
    ) {
    }

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if ($user && $this->mfa->requiresMfa($user) && ! $this->mfa->hasRememberedDevice($request, $user)) {
            $this->mfa->issueChallenge($user);
            $this->auditLogs->log($request, 'auth.mfa_issued', $user, [], $user);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('auth.pending_mfa_user_id', $user->id);
            $request->session()->put('auth.pending_mfa_remember', $request->boolean('remember'));

            return redirect()->route('mfa.challenge')->with('status', 'A verification code has been sent to your email.');
        }

        $request->session()->regenerate();
        $this->auditLogs->log($request, 'auth.login', $user, [], $user);

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'social_worker') {
            return redirect('/social-worker/dashboard');
        }

        if ($user->role === 'approving_officer') {
            return redirect()->route('approving.dashboard');
        }

        if ($user->role === 'reporting_officer') {
            return redirect()->route('reporting.dashboard');
        }

        if ($user->role === 'service_provider') {
            return redirect()->route('service-provider.dashboard');
        }

        if ($user->role === 'gl_payment_processor') {
            return redirect()->route('gl-payment-processor.dashboard');
        }

        if ($user->role === 'referral_institution') {
            return redirect()->route('referral-institution.dashboard');
        }

        if ($user->role === 'referral_officer') {
            return redirect()->route('referral-officer.dashboard');
        }

        if ($user->role === 'technical_staff') {
            return redirect()->route('technical-staff.payouts.index');
        }

        if ($user->role === 'admin_staff') {
            return redirect()->route('admin-staff.payouts.index');
        }

        if ($user->role === 'budget_officer') {
            return redirect()->route('budget-officer.dashboard');
        }

        if ($user->role === 'accounting_officer') {
            return redirect()->route('accounting-officer.dashboard');
        }

        if ($user->role === 'accounting_approver') {
            return redirect()->route('accounting-approver.dashboard');
        }

        if ($user->role === 'cash_officer') {
            return redirect()->route('cash-officer.dashboard');
        }

        if ($user->role === 'cash_approver') {
            return redirect()->route('cash-approver.dashboard');
        }

        return redirect()->route('client.dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->auditLogs->log($request, 'auth.logout', $user, [], $user);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
