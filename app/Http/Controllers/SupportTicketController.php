<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function create(Request $request): View
    {
        return view('support.create', [
            'source' => (string) $request->input('source', 'general'),
            'prefillName' => trim((string) optional($request->user())->name),
            'prefillEmail' => (string) optional($request->user())->email,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->enforceTicketRateLimit($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'source' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'max:0'],
        ]);

        $this->ensureNoRecentDuplicateTicket($validated);

        SupportTicket::create([
            'user_id' => optional($request->user())->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'source' => $validated['source'] ?: 'general',
            'status' => 'open',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()
            ->route('support.create', ['source' => $validated['source'] ?: 'general'])
            ->with('success', 'Your support request has been submitted. Please wait for administrator follow-up.');
    }

    protected function enforceTicketRateLimit(Request $request): void
    {
        $ipKey = 'support-ticket:ip:'.$request->ip();
        $email = strtolower((string) $request->input('email'));
        $emailKey = 'support-ticket:email:'.$email;

        if (RateLimiter::tooManyAttempts($ipKey, 3)) {
            $minutes = (int) ceil(max(1, RateLimiter::availableIn($ipKey)) / 60);

            throw ValidationException::withMessages([
                'email' => "Too many support requests were submitted from this connection. Please wait {$minutes} minute(s) before trying again.",
            ]);
        }

        if ($email !== '' && RateLimiter::tooManyAttempts($emailKey, 2)) {
            $minutes = (int) ceil(max(1, RateLimiter::availableIn($emailKey)) / 60);

            throw ValidationException::withMessages([
                'email' => "Too many support requests were submitted using this email. Please wait {$minutes} minute(s) before trying again.",
            ]);
        }

        RateLimiter::hit($ipKey, 1800);

        if ($email !== '') {
            RateLimiter::hit($emailKey, 1800);
        }
    }

    protected function ensureNoRecentDuplicateTicket(array $validated): void
    {
        $duplicateExists = SupportTicket::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
            ->whereRaw('LOWER(subject) = ?', [strtolower(trim($validated['subject']))])
            ->whereRaw('LOWER(message) = ?', [strtolower(trim($validated['message']))])
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if (! $duplicateExists) {
            return;
        }

        throw ValidationException::withMessages([
            'message' => 'A similar support request was already submitted recently. Please wait for the existing ticket to be reviewed instead of sending the same request again.',
        ]);
    }
}
