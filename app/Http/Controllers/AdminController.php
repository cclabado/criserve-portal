<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\Relationship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    protected array $roles = [
        'admin',
        'client',
        'social_worker',
        'approving_officer',
    ];

    public function dashboard(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_applications' => Application::count(),
            'for_approval' => Application::where('status', 'for_approval')->count(),
            'released' => Application::where('status', 'released')->count(),
        ];

        $applications = Application::with(['client', 'assistanceType'])
            ->latest()
            ->take(8)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'applications',
        ));
    }

    public function libraries(): View
    {
        $assistanceTypes = AssistanceType::with('subtypes')->orderBy('name')->get();
        $relationships = Relationship::orderBy('name')->get();

        return view('admin.libraries', compact('assistanceTypes', 'relationships'));
    }

    public function users(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role') && $request->role !== 'all', function ($query) use ($request) {
                $query->where('role', $request->role);
            })
            ->orderBy('name')
            ->get();

        $roles = $this->roles;

        return view('admin.users', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'role' => (string) $request->input('role', 'all'),
            ],
        ]);
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:'.implode(',', $this->roles)],
        ]);

        if ($redirect = $this->guardRoleChange($request->user(), $user, $validated['role'])) {
            return $redirect;
        }

        $user->update([
            'role' => $validated['role'],
        ]);

        return redirect()
            ->to(route('admin.users'))
            ->with('success', 'User role updated successfully.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'extension_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'birthdate' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'max:255'],
            'civil_status' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:'.implode(',', $this->roles)],
        ]);

        if ($redirect = $this->guardRoleChange($request->user(), $user, $validated['role'])) {
            return $redirect;
        }

        $user->update([
            ...$validated,
            'name' => trim(implode(' ', array_filter([
                $validated['first_name'],
                $validated['middle_name'] ?? null,
                $validated['last_name'],
                $validated['extension_name'] ?? null,
            ]))),
        ]);

        return redirect()
            ->to(route('admin.users'))
            ->with('success', 'User details updated successfully.');
    }

    protected function guardRoleChange(User $actingUser, User $targetUser, string $newRole): ?RedirectResponse
    {
        if ($targetUser->id === $actingUser->id && $newRole !== 'admin') {
            return redirect()
                ->to(route('admin.users'))
                ->withErrors(['role' => 'You cannot remove your own administrator access.']);
        }

        if ($targetUser->role === 'admin' && $newRole !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()
                ->to(route('admin.users'))
                ->withErrors(['role' => 'At least one administrator must remain assigned.']);
        }

        return null;
    }

    public function storeAssistanceType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:assistance_types,name'],
        ]);

        AssistanceType::create($validated);

        return redirect()
            ->to(route('admin.libraries'))
            ->with('success', 'Assistance type added successfully.');
    }

    public function storeAssistanceSubtype(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assistance_type_id' => ['required', 'exists:assistance_types,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $exists = AssistanceSubtype::where('assistance_type_id', $validated['assistance_type_id'])
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['subtype_name' => 'This subtype already exists for the selected assistance type.']);
        }

        AssistanceSubtype::create($validated);

        return redirect()
            ->to(route('admin.libraries'))
            ->with('success', 'Assistance subtype added successfully.');
    }

    public function storeRelationship(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:relationships,name'],
        ]);

        Relationship::create($validated);

        return redirect()
            ->to(route('admin.libraries'))
            ->with('success', 'Relationship library item added successfully.');
    }
}
