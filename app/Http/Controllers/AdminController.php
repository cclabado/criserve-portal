<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AssistanceDetail;
use App\Models\AssistanceFrequencyRule;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\ModeOfAssistance;
use App\Models\Relationship;
use App\Models\ReferralInstitution;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            'open_support_tickets' => SupportTicket::where('status', 'open')->count(),
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

    public function supportTickets(Request $request): View
    {
        $tickets = SupportTicket::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status') && $request->status !== 'all', function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.support-tickets', [
            'tickets' => $tickets,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'status' => (string) $request->input('status', 'all'),
            ],
        ]);
    }

    public function updateSupportTicket(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $supportTicket->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->to(route('admin.support-tickets'))
            ->with('success', 'Support ticket status updated successfully.');
    }

    public function libraries(): RedirectResponse
    {
        return redirect()->route('admin.libraries.show', 'assistance-types');
    }

    public function showLibrary(Request $request, string $library): View
    {
        $definition = $this->resolveLibraryDefinition($library);
        $status = (string) $request->input('status', 'active');
        $search = trim((string) $request->input('search', ''));

        $query = $definition['model']::query()
            ->with($definition['with'] ?? [])
            ->when($status === 'active', fn ($builder) => $builder->where('is_active', true))
            ->when($status === 'archived', fn ($builder) => $builder->where('is_active', false))
            ->when($search !== '', function ($builder) use ($search, $definition) {
                $builder->where(function ($inner) use ($search, $definition) {
                    foreach ($definition['search_columns'] as $column) {
                        $inner->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->orderBy($definition['order_by']);

        $items = $query->paginate(12)->withQueryString();

        return view('admin.libraries', [
            'definition' => $definition,
            'definitions' => $this->libraryDefinitions(),
            'items' => $items,
            'filters' => [
                'search' => $search,
                'status' => in_array($status, ['active', 'archived', 'all'], true) ? $status : 'active',
            ],
            'formOptions' => $this->libraryFormOptions(),
        ]);
    }

    public function frequencyRules(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $rules = AssistanceFrequencyRule::query()
            ->with(['subtype.type', 'detail'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('rule_type', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('subtype', function ($subtypeQuery) use ($search) {
                            $subtypeQuery->where('name', 'like', "%{$search}%")
                                ->orWhereHas('type', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"));
                        })
                        ->orWhereHas('detail', fn ($detailQuery) => $detailQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy(
                AssistanceType::select('assistance_types.name')
                    ->join('assistance_subtypes', 'assistance_subtypes.assistance_type_id', '=', 'assistance_types.id')
                    ->whereColumn('assistance_subtypes.id', 'assistance_frequency_rules.assistance_subtype_id')
                    ->limit(1)
            )
            ->orderBy(
                AssistanceSubtype::select('assistance_subtypes.name')
                    ->whereColumn('assistance_subtypes.id', 'assistance_frequency_rules.assistance_subtype_id')
                    ->limit(1)
            )
            ->orderBy(
                AssistanceDetail::select('assistance_details.name')
                    ->whereColumn('assistance_details.id', 'assistance_frequency_rules.assistance_detail_id')
                    ->limit(1)
            )
            ->paginate(12)
            ->withQueryString();

        return view('admin.frequency-rules', [
            'rules' => $rules,
            'filters' => [
                'search' => $search,
            ],
            'formOptions' => $this->frequencyRuleFormOptions(),
            'ruleTypes' => $this->frequencyRuleTypes(),
        ]);
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
        return $this->storeLibraryRecord($request, 'assistance-types');
    }

    public function storeAssistanceSubtype(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'assistance-subtypes');
    }

    public function storeAssistanceDetail(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'assistance-details');
    }

    public function storeModeOfAssistance(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'modes-of-assistance');
    }

    public function storeRelationship(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'relationships');
    }

    public function storeReferralInstitution(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'referral-institutions');
    }

    public function storeFrequencyRule(Request $request): RedirectResponse
    {
        AssistanceFrequencyRule::create($this->validateFrequencyRulePayload($request));

        return redirect()
            ->route('admin.frequency-rules')
            ->with('success', 'Frequency rule added successfully.');
    }

    public function updateFrequencyRule(Request $request, AssistanceFrequencyRule $frequencyRule): RedirectResponse
    {
        $frequencyRule->update($this->validateFrequencyRulePayload($request, $frequencyRule->id));

        return redirect()
            ->route('admin.frequency-rules')
            ->with('success', 'Frequency rule updated successfully.');
    }

    public function destroyFrequencyRule(AssistanceFrequencyRule $frequencyRule): RedirectResponse
    {
        $frequencyRule->delete();

        return redirect()
            ->route('admin.frequency-rules')
            ->with('success', 'Frequency rule removed successfully.');
    }

    public function updateLibrary(Request $request, string $library, int $item): RedirectResponse
    {
        $definition = $this->resolveLibraryDefinition($library);
        $model = $definition['model']::query()->findOrFail($item);
        $validated = $this->validateLibraryPayload($request, $library, $model->id);

        $model->update($validated);

        return redirect()
            ->route('admin.libraries.show', $library)
            ->with('success', $definition['singular'].' updated successfully.');
    }

    public function archiveLibrary(string $library, int $item): RedirectResponse
    {
        $definition = $this->resolveLibraryDefinition($library);
        $model = $definition['model']::query()->findOrFail($item);

        $model->update(['is_active' => false]);

        return redirect()
            ->route('admin.libraries.show', $library)
            ->with('success', $definition['singular'].' archived successfully.');
    }

    protected function storeLibraryRecord(Request $request, string $library): RedirectResponse
    {
        $definition = $this->resolveLibraryDefinition($library);
        $validated = $this->validateLibraryPayload($request, $library);
        $definition['model']::create($validated + ['is_active' => true]);

        return redirect()
            ->route('admin.libraries.show', $library)
            ->with('success', $definition['singular'].' added successfully.');
    }

    protected function validateLibraryPayload(Request $request, string $library, ?int $ignoreId = null): array
    {
        return match ($library) {
            'assistance-types' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('assistance_types', 'name')->ignore($ignoreId)],
            ]),
            'assistance-subtypes' => $this->validateAssistanceSubtypePayload($request, $ignoreId),
            'assistance-details' => $this->validateAssistanceDetailPayload($request, $ignoreId),
            'modes-of-assistance' => $this->validateModeOfAssistancePayload($request, $ignoreId),
            'relationships' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('relationships', 'name')->ignore($ignoreId)],
            ]),
            'referral-institutions' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('referral_institutions', 'name')->ignore($ignoreId)],
                'addressee' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
                'email' => ['nullable', 'email', 'max:255'],
                'contact_number' => ['nullable', 'string', 'max:255'],
            ]),
            default => throw new NotFoundHttpException(),
        };
    }

    protected function validateAssistanceSubtypePayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'assistance_type_id' => ['required', 'exists:assistance_types,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $query = AssistanceSubtype::query()
            ->where('assistance_type_id', $validated['assistance_type_id'])
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])]);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'This subtype already exists for the selected assistance type.',
            ]);
        }

        return $validated;
    }

    protected function validateAssistanceDetailPayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'assistance_subtype_id' => ['required', 'exists:assistance_subtypes,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $query = AssistanceDetail::query()
            ->where('assistance_subtype_id', $validated['assistance_subtype_id'])
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])]);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'This assistance detail already exists for the selected subtype.',
            ]);
        }

        return $validated;
    }

    protected function validateModeOfAssistancePayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('mode_of_assistances', 'name')->ignore($ignoreId)],
        ]);

        if (strtolower(trim($validated['name'])) === 'referral') {
            throw ValidationException::withMessages([
                'name' => 'Referral is managed through the Referral Institution library, not as a mode of assistance.',
            ]);
        }

        return $validated;
    }

    protected function validateFrequencyRulePayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'assistance_type_id' => ['nullable', 'exists:assistance_types,id'],
            'assistance_subtype_id' => ['required', 'exists:assistance_subtypes,id'],
            'assistance_detail_id' => ['nullable', 'exists:assistance_details,id'],
            'rule_type' => ['required', Rule::in(array_keys($this->frequencyRuleTypes()))],
            'interval_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'requires_reference_date' => ['nullable', 'boolean'],
            'requires_case_key' => ['nullable', 'boolean'],
            'allows_exception_request' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $subtype = AssistanceSubtype::query()
            ->with('type')
            ->findOrFail((int) $validated['assistance_subtype_id']);

        if (! empty($validated['assistance_type_id']) && (int) $validated['assistance_type_id'] !== (int) $subtype->assistance_type_id) {
            throw ValidationException::withMessages([
                'assistance_subtype_id' => 'The selected subtype does not belong to the selected assistance type.',
            ]);
        }

        $detailId = ! empty($validated['assistance_detail_id']) ? (int) $validated['assistance_detail_id'] : null;

        if ($detailId) {
            $detail = AssistanceDetail::query()->findOrFail($detailId);

            if ((int) $detail->assistance_subtype_id !== (int) $subtype->id) {
                throw ValidationException::withMessages([
                    'assistance_detail_id' => 'The selected detail does not belong to the selected subtype.',
                ]);
            }
        }

        if (! in_array($validated['rule_type'], ['every_n_months', 'every_n_months_review'], true)) {
            $validated['interval_months'] = null;
        }

        if (in_array($validated['rule_type'], ['every_n_months', 'every_n_months_review'], true) && empty($validated['interval_months'])) {
            throw ValidationException::withMessages([
                'interval_months' => 'Interval months is required for month-based rules.',
            ]);
        }

        $existingRuleQuery = AssistanceFrequencyRule::query()
            ->where('assistance_subtype_id', $subtype->id)
            ->when($detailId, fn ($query) => $query->where('assistance_detail_id', $detailId), fn ($query) => $query->whereNull('assistance_detail_id'));

        if ($ignoreId) {
            $existingRuleQuery->whereKeyNot($ignoreId);
        }

        if ($existingRuleQuery->exists()) {
            throw ValidationException::withMessages([
                'assistance_detail_id' => $detailId
                    ? 'A frequency rule already exists for this assistance detail.'
                    : 'A frequency rule already exists for this assistance subtype.',
            ]);
        }

        return [
            'assistance_subtype_id' => $subtype->id,
            'assistance_detail_id' => $detailId,
            'rule_type' => $validated['rule_type'],
            'interval_months' => $validated['interval_months'] ?? null,
            'requires_reference_date' => (bool) ($validated['requires_reference_date'] ?? false),
            'requires_case_key' => (bool) ($validated['requires_case_key'] ?? false),
            'allows_exception_request' => (bool) ($validated['allows_exception_request'] ?? false),
            'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
        ];
    }

    protected function libraryDefinitions(): array
    {
        return [
            'assistance-types' => [
                'title' => 'Assistance Types',
                'singular' => 'Assistance type',
                'description' => 'Manage the top-level assistance categories used across intake, assessment, and approvals.',
                'model' => AssistanceType::class,
                'order_by' => 'name',
                'search_columns' => ['name'],
                'with' => [],
                'icon' => 'category',
            ],
            'assistance-subtypes' => [
                'title' => 'Assistance Subtypes',
                'singular' => 'Assistance subtype',
                'description' => 'Maintain the specific assistance records grouped under each assistance type.',
                'model' => AssistanceSubtype::class,
                'order_by' => 'name',
                'search_columns' => ['name'],
                'with' => ['type'],
                'icon' => 'fork_right',
            ],
            'assistance-details' => [
                'title' => 'Assistance Details',
                'singular' => 'Assistance detail',
                'description' => 'Define the detailed assistance options that sit under each assistance subtype.',
                'model' => AssistanceDetail::class,
                'order_by' => 'name',
                'search_columns' => ['name'],
                'with' => ['subtype.type'],
                'icon' => 'list_alt',
            ],
            'modes-of-assistance' => [
                'title' => 'Modes of Assistance',
                'singular' => 'Mode of assistance',
                'description' => 'Configure the delivery modes available during assessment and recommendation.',
                'model' => ModeOfAssistance::class,
                'order_by' => 'name',
                'search_columns' => ['name'],
                'with' => [],
                'icon' => 'tactic',
            ],
            'relationships' => [
                'title' => 'Relationships',
                'singular' => 'Relationship',
                'description' => 'Manage household and beneficiary relationship labels used in family records.',
                'model' => Relationship::class,
                'order_by' => 'name',
                'search_columns' => ['name'],
                'with' => [],
                'icon' => 'family_restroom',
            ],
            'referral-institutions' => [
                'title' => 'Referral Institutions',
                'singular' => 'Referral institution',
                'description' => 'Maintain active government agencies and partner institutions for referral-based assistance.',
                'model' => ReferralInstitution::class,
                'order_by' => 'name',
                'search_columns' => ['name', 'addressee', 'email', 'contact_number', 'address'],
                'with' => [],
                'icon' => 'domain',
            ],
        ];
    }

    protected function resolveLibraryDefinition(string $library): array
    {
        $definitions = $this->libraryDefinitions();

        if (! isset($definitions[$library])) {
            throw new NotFoundHttpException();
        }

        return ['key' => $library] + $definitions[$library];
    }

    protected function libraryFormOptions(): array
    {
        return [
            'assistanceTypes' => AssistanceType::where('is_active', true)->orderBy('name')->get(),
            'assistanceSubtypes' => AssistanceSubtype::with('type')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }

    protected function frequencyRuleFormOptions(): array
    {
        return [
            'assistanceTypes' => AssistanceType::where('is_active', true)->orderBy('name')->get(),
            'assistanceSubtypes' => AssistanceSubtype::with('type')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'assistanceDetails' => AssistanceDetail::with('subtype.type')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }

    protected function frequencyRuleTypes(): array
    {
        return [
            'once_per_year' => 'Once Per Year',
            'every_n_months' => 'Every N Months',
            'every_n_months_review' => 'Every N Months With Review',
            'per_incident' => 'Per Incident',
            'per_admission' => 'Per Admission',
        ];
    }
}
