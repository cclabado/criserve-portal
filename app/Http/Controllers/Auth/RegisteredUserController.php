<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\FamilyNetworkService;
use App\Services\IdentityMappingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected IdentityMappingService $identityMapping,
        protected FamilyNetworkService $familyNetwork,
        protected AuditLogService $auditLogs
    ) {
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->enforceRegistrationRateLimit($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'extension_name' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['required', 'date'],
            'sex' => ['required', 'in:Male,Female'],
            'civil_status' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $this->ensureEmailIsAvailable((string) $validated['email']);
        $this->ensureIdentityIsUnique($validated);

        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?: null,
            'last_name' => $validated['last_name'],
            'extension_name' => $validated['extension_name'] ?: null,
            'birthdate' => $validated['birthdate'],
            'sex' => $validated['sex'],
            'civil_status' => $validated['civil_status'],
        ]);

        $client = Client::updateOrCreate(
            ['user_id' => $user->id],
            [
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?: null,
                'extension_name' => $validated['extension_name'] ?: null,
                'birthdate' => $validated['birthdate'],
                'sex' => $validated['sex'],
                'civil_status' => $validated['civil_status'],
            ]
        );

        $this->familyNetwork->syncClient($client);
        $this->identityMapping->syncUserMappings($user);
        $this->identityMapping->syncClientFamilyComposition($client, $user);

        event(new Registered($user));

        Auth::login($user);
        $this->auditLogs->log($request, 'auth.register', $user, [], $user);

        return redirect()->route('client.dashboard');
    }

    protected function enforceRegistrationRateLimit(Request $request): void
    {
        $ipKey = 'register:ip:'.$request->ip();
        $emailKey = 'register:email:'.strtolower((string) $request->input('email'));

        if (RateLimiter::tooManyAttempts($ipKey, 3)) {
            $minutes = (int) ceil(max(1, RateLimiter::availableIn($ipKey)) / 60);

            throw ValidationException::withMessages([
                'email' => "Too many registration attempts from this connection. Please wait {$minutes} minute(s) before trying again.",
            ]);
        }

        if (filled($request->input('email')) && RateLimiter::tooManyAttempts($emailKey, 2)) {
            $minutes = (int) ceil(max(1, RateLimiter::availableIn($emailKey)) / 60);

            throw ValidationException::withMessages([
                'email' => "Too many registration attempts were made using this email. Please wait {$minutes} minute(s) before trying again.",
            ]);
        }

        RateLimiter::hit($ipKey, 900);

        if (filled($request->input('email'))) {
            RateLimiter::hit($emailKey, 900);
        }
    }

    protected function ensureEmailIsAvailable(string $email): void
    {
        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if (! $existingUser) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => 'An account with this email already exists. Please sign in instead, use Forgot Password, or contact the administrator for account recovery.',
        ]);
    }

    protected function ensureIdentityIsUnique(array $validated): void
    {
        $existingUser = User::query()
            ->where('role', 'client')
            ->whereRaw('LOWER(last_name) = ?', [strtolower(trim((string) $validated['last_name']))])
            ->whereRaw('LOWER(first_name) = ?', [strtolower(trim((string) $validated['first_name']))])
            ->whereRaw('LOWER(COALESCE(middle_name, \'\')) = ?', [strtolower(trim((string) ($validated['middle_name'] ?? '')))])
            ->whereRaw('LOWER(COALESCE(extension_name, \'\')) = ?', [strtolower(trim((string) ($validated['extension_name'] ?? '')))])
            ->whereDate('birthdate', $validated['birthdate'])
            ->first();

        if (! $existingUser) {
            return;
        }

        throw ValidationException::withMessages([
            'first_name' => 'Only one client account is allowed per person. A client account already exists for these personal details. Please sign in, use Forgot Password if you still have access to your email, or contact support if you need help recovering an old account with a lost email.',
        ]);
    }
}
