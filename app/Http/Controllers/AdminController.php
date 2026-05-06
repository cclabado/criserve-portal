<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Application;
use App\Models\AssistanceDetail;
use App\Models\AssistanceDocumentRequirement;
use App\Models\AssistanceFrequencyRule;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\ModeOfAssistance;
use App\Models\Position;
use App\Models\Relationship;
use App\Models\ReferralInstitution;
use App\Models\ServicePoint;
use App\Models\ServiceProvider;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    protected array $roles = [
        'admin',
        'client',
        'social_worker',
        'approving_officer',
        'service_provider',
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

    public function reports(Request $request): View|StreamedResponse
    {
        $filters = $this->resolveReportFilters($request);
        $query = $this->buildReportQuery($filters);

        if ($request->input('format') === 'csv') {
            return $this->downloadReportCsv(clone $query, $filters);
        }

        $applications = (clone $query)
            ->with(['client', 'assistanceType', 'assistanceSubtype', 'modeOfAssistance', 'socialWorker', 'approvingOfficer'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total_applications' => (clone $query)->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'denied' => (clone $query)->where('status', 'denied')->count(),
            'released' => (clone $query)->where('status', 'released')->count(),
            'for_approval' => (clone $query)->where('status', 'for_approval')->count(),
            'total_amount' => round((float) ((clone $query)->sum('final_amount') ?: 0), 2),
            'recommended_amount' => round((float) ((clone $query)->sum('recommended_amount') ?: 0), 2),
            'amount_needed' => round((float) ((clone $query)->sum('amount_needed') ?: 0), 2),
        ];

        $statusBreakdown = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->pluck('total', 'status');

        $typeBreakdown = (clone $query)
            ->leftJoin('assistance_types', 'assistance_types.id', '=', 'applications.assistance_type_id')
            ->selectRaw("COALESCE(assistance_types.name, 'Unassigned') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'label');

        $sectorBreakdown = (clone $query)
            ->selectRaw("COALESCE(NULLIF(client_sector, ''), 'Unassigned') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'label');

        $options = $this->reportFilterOptions();

        return view('admin.reports', compact(
            'applications',
            'filters',
            'summary',
            'statusBreakdown',
            'typeBreakdown',
            'sectorBreakdown',
            'options',
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
            ->with('position')
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
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
            'serviceProviders' => ServiceProvider::where('is_active', true)->orderBy('name')->get(),
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

        if ($validated['role'] === 'service_provider' && empty($validated['service_provider_id'])) {
            throw ValidationException::withMessages([
                'service_provider_id' => 'Select the linked service provider for this account.',
            ]);
        }

        $user->update([
            'role' => $validated['role'],
        ]);
        $this->auditLogs->log($request, 'user.role_updated', $user, [
            'new_role' => $validated['role'],
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
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'position_id' => ['nullable', Rule::exists('positions', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'license_number' => ['nullable', 'string', 'max:255'],
            'approval_min_amount' => ['nullable', 'numeric', 'min:0'],
            'approval_max_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($redirect = $this->guardRoleChange($request->user(), $user, $validated['role'])) {
            return $redirect;
        }

        $this->validateStaffPositionDetails($validated);
        $this->validateApprovingOfficerRange($validated);

        $position = ! empty($validated['position_id'])
            ? Position::query()->find((int) $validated['position_id'])
            : null;

        $user->update([
            ...$validated,
            'service_provider_id' => $validated['role'] === 'service_provider'
                ? ($validated['service_provider_id'] ?? null)
                : null,
            'position_id' => in_array($validated['role'], ['social_worker', 'approving_officer'], true)
                ? ($validated['position_id'] ?? null)
                : null,
            'license_number' => $position?->requires_license_number
                ? (filled($validated['license_number'] ?? null) ? trim((string) $validated['license_number']) : null)
                : null,
            'approval_min_amount' => $validated['role'] === 'approving_officer'
                ? ($this->normalizeMoneyValue($validated['approval_min_amount'] ?? null) ?? 0.0)
                : null,
            'approval_max_amount' => $validated['role'] === 'approving_officer'
                ? $this->normalizeMoneyValue($validated['approval_max_amount'] ?? null)
                : null,
            'name' => trim(implode(' ', array_filter([
                $validated['first_name'],
                $validated['middle_name'] ?? null,
                $validated['last_name'],
                $validated['extension_name'] ?? null,
            ]))),
        ]);
        $this->auditLogs->log($request, 'user.updated', $user, [
            'role' => $validated['role'],
            'email' => $validated['email'],
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

    protected function validateApprovingOfficerRange(array $validated): void
    {
        if (($validated['role'] ?? null) !== 'approving_officer') {
            return;
        }

        $minimumAmount = $this->normalizeMoneyValue($validated['approval_min_amount'] ?? null) ?? 0.0;
        $maximumAmount = $this->normalizeMoneyValue($validated['approval_max_amount'] ?? null);

        if ($maximumAmount !== null && $maximumAmount < $minimumAmount) {
            throw ValidationException::withMessages([
                'approval_max_amount' => 'Maximum approval amount must be greater than or equal to the minimum approval amount.',
            ]);
        }
    }

    protected function normalizeMoneyValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    protected function validateStaffPositionDetails(array $validated): void
    {
        if (! in_array($validated['role'] ?? null, ['social_worker', 'approving_officer'], true)) {
            return;
        }

        if (empty($validated['position_id'])) {
            throw ValidationException::withMessages([
                'position_id' => 'Position is required for social workers and approving officers.',
            ]);
        }

        $position = Position::query()->find((int) $validated['position_id']);

        if ($position?->requires_license_number && blank($validated['license_number'] ?? null)) {
            throw ValidationException::withMessages([
                'license_number' => 'License number is required for the selected position.',
            ]);
        }
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

    public function storeClientType(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'client-types');
    }

    public function storePosition(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'positions');
    }

    public function storeReferralInstitution(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'referral-institutions');
    }

    public function storeDocumentRequirement(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'document-requirements');
    }

    public function storeServicePoint(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'service-points');
    }

    public function storeServiceProvider(Request $request): RedirectResponse
    {
        return $this->storeLibraryRecord($request, 'service-providers');
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

        $model->update($validated + ['is_active' => $request->boolean('is_active', true)]);

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

    public function restoreLibrary(string $library, int $item): RedirectResponse
    {
        $definition = $this->resolveLibraryDefinition($library);
        $model = $definition['model']::query()->findOrFail($item);

        $model->update(['is_active' => true]);

        return redirect()
            ->route('admin.libraries.show', $library)
            ->with('success', $definition['singular'].' reactivated successfully.');
    }

    protected function storeLibraryRecord(Request $request, string $library): RedirectResponse
    {
        $definition = $this->resolveLibraryDefinition($library);
        $validated = $this->validateLibraryPayload($request, $library);
        $definition['model']::create($validated + ['is_active' => $request->boolean('is_active', true)]);

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
            'document-requirements' => $this->validateDocumentRequirementPayload($request, $ignoreId),
            'modes-of-assistance' => $this->validateModeOfAssistancePayload($request, $ignoreId),
            'service-points' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('service_points', 'name')->ignore($ignoreId)],
            ]),
            'service-providers' => $this->validateServiceProviderPayload($request, $ignoreId),
            'positions' => $this->validatePositionPayload($request, $ignoreId),
            'relationships' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('relationships', 'name')->ignore($ignoreId)],
            ]),
            'client-types' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('client_types', 'name')->ignore($ignoreId)],
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
            'minimum_amount' => ['nullable', 'numeric', 'min:0'],
            'maximum_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (strtolower(trim($validated['name'])) === 'referral') {
            throw ValidationException::withMessages([
                'name' => 'Referral is managed through the Referral Institution library, not as a mode of assistance.',
            ]);
        }

        $minimumAmount = filled($validated['minimum_amount'] ?? null)
            ? round((float) $validated['minimum_amount'], 2)
            : null;
        $maximumAmount = filled($validated['maximum_amount'] ?? null)
            ? round((float) $validated['maximum_amount'], 2)
            : null;

        if ($minimumAmount !== null && $maximumAmount !== null && $maximumAmount < $minimumAmount) {
            throw ValidationException::withMessages([
                'maximum_amount' => 'Maximum amount must be greater than or equal to the minimum amount.',
            ]);
        }

        return [
            'name' => trim((string) $validated['name']),
            'minimum_amount' => $minimumAmount,
            'maximum_amount' => $maximumAmount,
        ];
    }

    protected function validateDocumentRequirementPayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'assistance_subtype_id' => ['required', 'exists:assistance_subtypes,id'],
            'assistance_detail_id' => ['nullable', 'exists:assistance_details,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
            'applies_when_amount_exceeds' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $subtype = AssistanceSubtype::query()
            ->with('type')
            ->findOrFail((int) $validated['assistance_subtype_id']);

        $detailId = ! empty($validated['assistance_detail_id']) ? (int) $validated['assistance_detail_id'] : null;

        if ($detailId) {
            $detail = AssistanceDetail::query()->findOrFail($detailId);

            if ((int) $detail->assistance_subtype_id !== (int) $subtype->id) {
                throw ValidationException::withMessages([
                    'assistance_detail_id' => 'The selected detail does not belong to the selected subtype.',
                ]);
            }
        }

        $query = AssistanceDocumentRequirement::query()
            ->where('assistance_subtype_id', $subtype->id)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) $validated['name']))])
            ->when(
                $detailId,
                fn ($builder) => $builder->where('assistance_detail_id', $detailId),
                fn ($builder) => $builder->whereNull('assistance_detail_id')
            );

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => $detailId
                    ? 'This document requirement already exists for the selected assistance detail.'
                    : 'This document requirement already exists for the selected assistance subtype.',
            ]);
        }

        return [
            'assistance_subtype_id' => $subtype->id,
            'assistance_detail_id' => $detailId,
            'name' => trim((string) $validated['name']),
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'applies_when_amount_exceeds' => filled($validated['applies_when_amount_exceeds'] ?? null)
                ? round((float) $validated['applies_when_amount_exceeds'], 2)
                : null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }

    protected function validateServiceProviderPayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'addressee' => ['nullable', 'string', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', Rule::in(ServiceProvider::CATEGORY_OPTIONS)],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $query = ServiceProvider::query()
            ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) $validated['name']))]);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'This service provider already exists.',
            ]);
        }

        return [
            'name' => trim((string) $validated['name']),
            'addressee' => filled($validated['addressee'] ?? null) ? trim((string) $validated['addressee']) : null,
            'categories' => collect($validated['categories'] ?? [])
                ->filter(fn ($category) => in_array($category, ServiceProvider::CATEGORY_OPTIONS, true))
                ->unique()
                ->values()
                ->all(),
            'contact_number' => filled($validated['contact_number'] ?? null) ? trim((string) $validated['contact_number']) : null,
            'address' => filled($validated['address'] ?? null) ? trim((string) $validated['address']) : null,
            'email' => filled($validated['email'] ?? null) ? trim((string) $validated['email']) : null,
        ];
    }

    protected function validatePositionPayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position_code' => ['nullable', 'string', 'max:255'],
            'salary_grade' => ['nullable', 'integer', 'min:1', 'max:33'],
            'requires_license_number' => ['nullable', 'boolean'],
        ]);

        $name = trim((string) $validated['name']);
        $positionCode = filled($validated['position_code'] ?? null)
            ? strtoupper(trim((string) $validated['position_code']))
            : null;

        $query = Position::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)]);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'This position already exists.',
            ]);
        }

        return [
            'name' => $name,
            'position_code' => $positionCode,
            'salary_grade' => ! empty($validated['salary_grade']) ? (int) $validated['salary_grade'] : null,
            'requires_license_number' => (bool) ($validated['requires_license_number'] ?? false),
        ];
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

    protected function resolveReportFilters(Request $request): array
    {
        $validated = $request->validate([
            'report_type' => ['nullable', Rule::in(['daily', 'monthly', 'yearly', 'custom'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
            'assistance_type_id' => ['nullable', 'exists:assistance_types,id'],
            'assistance_subtype_id' => ['nullable', 'exists:assistance_subtypes,id'],
            'mode_of_assistance_id' => ['nullable', 'exists:mode_of_assistances,id'],
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'social_worker_id' => ['nullable', 'exists:users,id'],
            'approving_officer_id' => ['nullable', 'exists:users,id'],
            'service_point' => ['nullable', 'string', 'max:255'],
            'client_sector' => ['nullable', 'string', 'max:255'],
            'client_sub_category' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', 'max:255'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $reportType = $validated['report_type'] ?? 'daily';
        $today = now()->startOfDay();

        [$dateFrom, $dateTo] = match ($reportType) {
            'monthly' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'yearly' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'custom' => [
                ! empty($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : null,
                ! empty($validated['date_to']) ? Carbon::parse($validated['date_to'])->endOfDay() : null,
            ],
            default => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
        };

        if ($dateFrom && $dateTo && $dateTo->lt($dateFrom)) {
            throw ValidationException::withMessages([
                'date_to' => 'Date to must be on or after date from.',
            ]);
        }

        return [
            'report_type' => $reportType,
            'date_from' => $dateFrom?->toDateString(),
            'date_to' => $dateTo?->toDateString(),
            'status' => $validated['status'] ?? 'all',
            'assistance_type_id' => $validated['assistance_type_id'] ?? null,
            'assistance_subtype_id' => $validated['assistance_subtype_id'] ?? null,
            'mode_of_assistance_id' => $validated['mode_of_assistance_id'] ?? null,
            'service_provider_id' => $validated['service_provider_id'] ?? null,
            'social_worker_id' => $validated['social_worker_id'] ?? null,
            'approving_officer_id' => $validated['approving_officer_id'] ?? null,
            'service_point' => $validated['service_point'] ?? 'all',
            'client_sector' => $validated['client_sector'] ?? 'all',
            'client_sub_category' => $validated['client_sub_category'] ?? 'all',
            'sex' => $validated['sex'] ?? 'all',
            'min_amount' => isset($validated['min_amount']) ? (float) $validated['min_amount'] : null,
            'max_amount' => isset($validated['max_amount']) ? (float) $validated['max_amount'] : null,
        ];
    }

    protected function buildReportQuery(array $filters)
    {
        $query = Application::query();

        if (! empty($filters['date_from'])) {
            $query->whereDate('applications.created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('applications.created_at', '<=', $filters['date_to']);
        }

        $query
            ->when(($filters['status'] ?? 'all') !== 'all', fn ($builder) => $builder->where('applications.status', $filters['status']))
            ->when(! empty($filters['assistance_type_id']), fn ($builder) => $builder->where('applications.assistance_type_id', $filters['assistance_type_id']))
            ->when(! empty($filters['assistance_subtype_id']), fn ($builder) => $builder->where('applications.assistance_subtype_id', $filters['assistance_subtype_id']))
            ->when(! empty($filters['mode_of_assistance_id']), fn ($builder) => $builder->where('applications.mode_of_assistance_id', $filters['mode_of_assistance_id']))
            ->when(! empty($filters['service_provider_id']), fn ($builder) => $builder->where('applications.service_provider_id', $filters['service_provider_id']))
            ->when(! empty($filters['social_worker_id']), fn ($builder) => $builder->where('applications.social_worker_id', $filters['social_worker_id']))
            ->when(! empty($filters['approving_officer_id']), fn ($builder) => $builder->where('applications.approving_officer_id', $filters['approving_officer_id']))
            ->when(($filters['service_point'] ?? 'all') !== 'all', fn ($builder) => $builder->where('applications.gis_visit_type', $filters['service_point']))
            ->when(($filters['client_sector'] ?? 'all') !== 'all', fn ($builder) => $builder->where('applications.client_sector', $filters['client_sector']))
            ->when(($filters['client_sub_category'] ?? 'all') !== 'all', fn ($builder) => $builder->where('applications.client_sub_category', $filters['client_sub_category']))
            ->when(($filters['sex'] ?? 'all') !== 'all', fn ($builder) => $builder->whereHas('client', fn ($clientQuery) => $clientQuery->where('sex', $filters['sex'])))
            ->when($filters['min_amount'] !== null, fn ($builder) => $builder->where(function ($amountQuery) use ($filters) {
                $amountQuery->where('applications.final_amount', '>=', $filters['min_amount'])
                    ->orWhere(function ($fallbackQuery) use ($filters) {
                        $fallbackQuery->whereNull('applications.final_amount')
                            ->where('applications.recommended_amount', '>=', $filters['min_amount']);
                    })
                    ->orWhere(function ($fallbackQuery) use ($filters) {
                        $fallbackQuery->whereNull('applications.final_amount')
                            ->whereNull('applications.recommended_amount')
                            ->where('applications.amount_needed', '>=', $filters['min_amount']);
                    });
            }))
            ->when($filters['max_amount'] !== null, fn ($builder) => $builder->where(function ($amountQuery) use ($filters) {
                $amountQuery->where('applications.final_amount', '<=', $filters['max_amount'])
                    ->orWhere(function ($fallbackQuery) use ($filters) {
                        $fallbackQuery->whereNull('applications.final_amount')
                            ->where('applications.recommended_amount', '<=', $filters['max_amount']);
                    })
                    ->orWhere(function ($fallbackQuery) use ($filters) {
                        $fallbackQuery->whereNull('applications.final_amount')
                            ->whereNull('applications.recommended_amount')
                            ->where('applications.amount_needed', '<=', $filters['max_amount']);
                    });
            }));

        return $query;
    }

    protected function reportFilterOptions(): array
    {
        return [
            'statuses' => Application::query()->select('status')->distinct()->orderBy('status')->pluck('status'),
            'assistanceTypes' => AssistanceType::where('is_active', true)->orderBy('name')->get(),
            'assistanceSubtypes' => AssistanceSubtype::where('is_active', true)->orderBy('name')->get(),
            'modesOfAssistance' => ModeOfAssistance::where('is_active', true)->orderBy('name')->get(),
            'serviceProviders' => ServiceProvider::where('is_active', true)->orderBy('name')->get(),
            'socialWorkers' => User::where('role', 'social_worker')->orderBy('name')->get(),
            'approvingOfficers' => User::where('role', 'approving_officer')->orderBy('name')->get(),
            'servicePoints' => Application::query()
                ->whereNotNull('gis_visit_type')
                ->where('gis_visit_type', '!=', '')
                ->select('gis_visit_type')
                ->distinct()
                ->orderBy('gis_visit_type')
                ->pluck('gis_visit_type'),
            'clientSectors' => Application::query()
                ->whereNotNull('client_sector')
                ->where('client_sector', '!=', '')
                ->select('client_sector')
                ->distinct()
                ->orderBy('client_sector')
                ->pluck('client_sector'),
            'clientSubCategories' => Application::query()
                ->whereNotNull('client_sub_category')
                ->where('client_sub_category', '!=', '')
                ->select('client_sub_category')
                ->distinct()
                ->orderBy('client_sub_category')
                ->pluck('client_sub_category'),
            'sexes' => Client::query()
                ->whereNotNull('sex')
                ->where('sex', '!=', '')
                ->select('sex')
                ->distinct()
                ->orderBy('sex')
                ->pluck('sex'),
        ];
    }

    protected function downloadReportCsv($query, array $filters): StreamedResponse
    {
        $filename = 'admin-report-'.$filters['report_type'].'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Reference No',
                'Created Date',
                'Client',
                'Sex',
                'Status',
                'Assistance Type',
                'Assistance Subtype',
                'Mode of Assistance',
                'Service Point',
                'Client Sector',
                'Client Sub Category',
                'Amount Needed',
                'Recommended Amount',
                'Final Amount',
                'Social Worker',
                'Approving Officer',
            ]);

            $query->with(['client', 'assistanceType', 'assistanceSubtype', 'modeOfAssistance', 'socialWorker', 'approvingOfficer'])
                ->orderBy('created_at')
                ->chunk(200, function ($applications) use ($handle) {
                    foreach ($applications as $application) {
                        fputcsv($handle, [
                            $application->reference_no,
                            optional($application->created_at)->format('Y-m-d H:i:s'),
                            trim(($application->client?->first_name ?? '').' '.($application->client?->last_name ?? '')),
                            $application->client?->sex,
                            $application->status,
                            $application->assistanceType?->name,
                            $application->assistanceSubtype?->name,
                            $application->modeOfAssistance?->name ?? $application->mode_of_assistance,
                            $application->gis_visit_type,
                            $application->client_sector,
                            $application->client_sub_category,
                            $application->amount_needed,
                            $application->recommended_amount,
                            $application->final_amount,
                            $application->socialWorker?->name,
                            $application->approvingOfficer?->name,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
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
            'document-requirements' => [
                'title' => 'Document Requirements',
                'singular' => 'Document requirement',
                'description' => 'Set the client-upload document checklist required for each assistance subtype or detail.',
                'model' => AssistanceDocumentRequirement::class,
                'order_by' => 'sort_order',
                'search_columns' => ['name', 'description'],
                'with' => ['subtype.type', 'detail'],
                'icon' => 'task_alt',
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
            'service-points' => [
                'title' => 'Service Points',
                'singular' => 'Service point',
                'description' => 'Manage the intake service point options used in the General Intake Sheet and assessment flow.',
                'model' => ServicePoint::class,
                'order_by' => 'name',
                'search_columns' => ['name'],
                'with' => [],
                'icon' => 'location_on',
            ],
            'service-providers' => [
                'title' => 'Service Providers',
                'singular' => 'Service provider',
                'description' => 'Manage hospitals, pharmacies, funeral parlors, and other providers that receive approved guarantee letters.',
                'model' => ServiceProvider::class,
                'order_by' => 'name',
                'search_columns' => ['name', 'addressee', 'email', 'contact_number', 'address'],
                'with' => ['accounts'],
                'icon' => 'local_hospital',
            ],
            'positions' => [
                'title' => 'Positions',
                'singular' => 'Position',
                'description' => 'Manage plantilla-style government position titles assigned to social workers, approving officers, and other staff accounts.',
                'model' => Position::class,
                'order_by' => 'name',
                'search_columns' => ['name', 'position_code'],
                'with' => [],
                'icon' => 'badge',
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
            'client-types' => [
                'title' => 'Client Types',
                'singular' => 'Client type',
                'description' => 'Manage the client type options used in the General Intake Sheet and social worker intake flow.',
                'model' => ClientType::class,
                'order_by' => 'name',
                'search_columns' => ['name'],
                'with' => [],
                'icon' => 'group',
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
            'assistanceDetails' => AssistanceDetail::with('subtype.type')
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
