<?php

namespace App\Http\Controllers;

use App\Models\AssistanceType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Client;
use App\Models\FamilyMember;
use App\Models\Relationship;
use App\Models\User;
use App\Services\FamilyNetworkService;
use App\Services\IdentityMappingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientDashboardController extends Controller
{
    public function __construct(
        protected FamilyNetworkService $familyNetwork
    ) {
    }

    protected function ensureClientProfile(): Client
    {
        $user = auth()->user();

        return Client::firstOrCreate(
            ['user_id' => $user->id],
            [
                'last_name' => $user->last_name,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'extension_name' => $user->extension_name,
                'contact_number' => $user->contact_number,
                'birthdate' => $user->birthdate,
                'sex' => $user->sex,
                'civil_status' => $user->civil_status,
                'full_address' => $user->full_address ?? $user->address,
            ]
        );
    }

    protected function applicationQuery()
    {
        $userId = auth()->id();

        return Application::with(['assistanceType', 'assistanceSubtype', 'assistanceDetail', 'frequencyRule'])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('beneficiaryProfile', function ($beneficiaryProfileQuery) use ($userId) {
                        $beneficiaryProfileQuery->where('linked_user_id', $userId);
                    })
                    ->orWhereHas('applicationFamilyMembers', function ($familyQuery) use ($userId) {
                        $familyQuery->where('linked_user_id', $userId);
                    });
            });
    }

    protected function applyApplicationFilters(Request $request, $query)
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('assistance_type_id', $request->type);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $applications = $this->applyApplicationFilters($request, $this->applicationQuery())
            ->latest()
            ->paginate(5)
            ->withQueryString();

        // IMPORTANT: latest APPLICATION regardless of filter
        $latestApplication = $this->applicationQuery()
            ->latest()
            ->first();

        $baseQuery = Application::query()->where('user_id', auth()->id());

        $statusSummary = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereIn('status', ['submitted', 'under_review', 'for_approval', 'approved'])->count(),
            'released' => (clone $baseQuery)->where('status', 'released')->count(),
            'cancelled' => (clone $baseQuery)->whereIn('status', ['cancelled', 'denied'])->count(),
        ];

        $trendStart = Carbon::today()->subDays(6);
        $dailyTrendRows = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereDate('created_at', '>=', $trendStart)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $trendLabels = [];
        $trendValues = [];

        for ($date = $trendStart->copy(); $date->lte(Carbon::today()); $date->addDay()) {
            $key = $date->toDateString();
            $trendLabels[] = $date->format('M d');
            $trendValues[] = (int) ($dailyTrendRows[$key]->total ?? 0);
        }

        $maxTrendValue = max(1, ...$trendValues);

        $statusBreakdown = [
            'Submitted' => (clone $baseQuery)->where('status', 'submitted')->count(),
            'Under Review' => (clone $baseQuery)->where('status', 'under_review')->count(),
            'For Approval' => (clone $baseQuery)->where('status', 'for_approval')->count(),
            'Released' => (clone $baseQuery)->where('status', 'released')->count(),
        ];

        $types = AssistanceType::where('is_active', true)->get();
        return view('client.dashboard', compact(
            'applications',
            'latestApplication',
            'types',
            'statusSummary',
            'trendLabels',
            'trendValues',
            'maxTrendValue',
            'statusBreakdown'
        ));
    }

    public function applications(Request $request)
    {
        $applications = $this->applyApplicationFilters($request, $this->applicationQuery())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $types = AssistanceType::where('is_active', true)->get();

        return view('client.applications', compact('applications', 'types'));
    }

    public function family()
    {
        $client = $this->ensureClientProfile()->load([
            'familyMembers' => function ($query) {
                $query->whereNull('beneficiary_profile_id')
                    ->whereNull('application_id')
                    ->with('linkedUser')
                    ->orderBy('id');
            },
        ]);

        $relationships = Relationship::query()
            ->where('is_active', true)
            ->where('name', '!=', 'Self')
            ->where('name', '!=', 'Other')
            ->orderBy('name')
            ->get();

        $possibleAccountMatches = $this->findPossibleFamilyAccountMatches($client);
        $suggestions = $this->buildFamilySuggestions($client, $relationships);
        $familyNetwork = $this->familyNetwork->buildClientNetwork($client);

        return view('client.family', compact('client', 'relationships', 'suggestions', 'possibleAccountMatches', 'familyNetwork'));
    }

    public function updateFamily(
        Request $request,
        IdentityMappingService $identityMapping,
        FamilyNetworkService $familyNetwork
    ) {
        $client = $this->ensureClientProfile();

        $validated = $request->validate([
            'family_id' => ['nullable', 'array'],
            'family_id.*' => ['nullable', 'integer'],
            'family_last_name' => ['required', 'array', 'min:1'],
            'family_last_name.*' => ['required', 'string', 'max:255'],
            'family_first_name' => ['required', 'array', 'min:1'],
            'family_first_name.*' => ['required', 'string', 'max:255'],
            'family_middle_name' => ['nullable', 'array'],
            'family_middle_name.*' => ['nullable', 'string', 'max:255'],
            'family_extension_name' => ['nullable', 'array'],
            'family_extension_name.*' => ['nullable', 'string', 'max:255'],
            'family_relationship' => ['required', 'array', 'min:1'],
            'family_relationship.*' => ['required', 'exists:relationships,id'],
            'family_birthdate' => ['required', 'array', 'min:1'],
            'family_birthdate.*' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($client, $validated, $identityMapping, $familyNetwork) {
            $existingMembers = $client->familyMembers()
                ->whereNull('beneficiary_profile_id')
                ->whereNull('application_id')
                ->get()
                ->keyBy('id');

            $keptIds = [];

            foreach ($validated['family_last_name'] as $index => $lastName) {
                $memberId = $validated['family_id'][$index] ?? null;
                $member = $memberId ? $existingMembers->get((int) $memberId) : null;

                if (! $member) {
                    $member = new FamilyMember([
                        'client_id' => $client->id,
                        'application_id' => null,
                        'beneficiary_profile_id' => null,
                    ]);
                }

                $member->fill([
                    'client_id' => $client->id,
                    'application_id' => null,
                    'beneficiary_profile_id' => null,
                    'last_name' => $lastName,
                    'first_name' => $validated['family_first_name'][$index] ?? null,
                    'middle_name' => $validated['family_middle_name'][$index] ?? null,
                    'extension_name' => $validated['family_extension_name'][$index] ?? null,
                    'relationship' => $validated['family_relationship'][$index] ?? null,
                    'birthdate' => $validated['family_birthdate'][$index] ?? null,
                ]);
                $member->save();

                $identityMapping->syncFamilyMember($member);
                $keptIds[] = $member->id;
            }

            $client->familyMembers()
                ->whereNull('beneficiary_profile_id')
                ->whereNull('application_id')
                ->when(! empty($keptIds), fn ($query) => $query->whereNotIn('id', $keptIds))
                ->when(empty($keptIds), fn ($query) => $query)
                ->delete();

            $familyNetwork->syncClient($client->fresh('user'));
        });

        return redirect()
            ->route('client.family')
            ->with('success', 'Family composition updated successfully.');
    }

    public function show($id)
    {
        $application = $this->applicationQuery()->with([
            'client',
            'beneficiary',
            'familyMembers',
            'documents',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'frequencyRule',
            'frequencyBasisApplication',
            'modeOfAssistance',
        ])->findOrFail($id);

        return view('client.application-details', compact('application'));
    }

    protected function buildFamilySuggestions(Client $client, $relationships)
    {
        $currentMembers = $client->familyMembers
            ->whereNull('beneficiary_profile_id')
            ->whereNull('application_id')
            ->values();

        if ($currentMembers->isEmpty()) {
            return collect();
        }

        $relationshipById = $relationships->keyBy(fn ($relationship) => (string) $relationship->id);
        $relationshipIdByName = $relationships
            ->mapWithKeys(fn ($relationship) => [Str::lower($relationship->name) => $relationship->id]);

        $knownSignatures = [];
        $knownSignatures[$this->identitySignature(
            $client->last_name,
            $client->first_name,
            $client->middle_name,
            $client->extension_name,
            $client->birthdate
        )] = true;

        foreach ($currentMembers as $member) {
            $knownSignatures[$this->identitySignature(
                $member->last_name,
                $member->first_name,
                $member->middle_name,
                $member->extension_name,
                $member->birthdate
            )] = true;
        }

        $suggestions = collect();
        $suggestedSignatures = [];

        foreach ($currentMembers as $sourceMember) {
            if (! $sourceMember->linked_user_id) {
                continue;
            }

            $linkedClient = $this->resolveLinkedClientHousehold((int) $sourceMember->linked_user_id);

            if (! $linkedClient) {
                continue;
            }

            $sourceRelationName = Str::lower((string) optional($relationshipById->get((string) $sourceMember->relationship))->name);
            $sourceSignature = $this->identitySignature(
                $sourceMember->last_name,
                $sourceMember->first_name,
                $sourceMember->middle_name,
                $sourceMember->extension_name,
                $sourceMember->birthdate
            );

            foreach ($linkedClient->familyMembers as $candidate) {
                $candidateSignature = $this->identitySignature(
                    $candidate->last_name,
                    $candidate->first_name,
                    $candidate->middle_name,
                    $candidate->extension_name,
                    $candidate->birthdate
                );

                if (
                    isset($knownSignatures[$candidateSignature]) ||
                    isset($suggestedSignatures[$candidateSignature]) ||
                    $candidateSignature === $sourceSignature
                ) {
                    continue;
                }

                $candidateRelationName = Str::lower((string) optional($relationshipById->get((string) $candidate->relationship))->name);
                $suggestedRelationshipName = $this->inferSuggestedRelationship($sourceRelationName, $candidateRelationName);

                if (! $suggestedRelationshipName) {
                    continue;
                }

                $suggestedRelationshipId = $relationshipIdByName->get(Str::lower($suggestedRelationshipName));

                if (! $suggestedRelationshipId) {
                    continue;
                }

                $suggestions->push([
                    'last_name' => $candidate->last_name,
                    'first_name' => $candidate->first_name,
                    'middle_name' => $candidate->middle_name,
                    'extension_name' => $candidate->extension_name,
                    'birthdate' => $candidate->birthdate,
                    'relationship' => (string) $suggestedRelationshipId,
                    'relationship_name' => $suggestedRelationshipName,
                    'linked_user_id' => $candidate->linked_user_id,
                    'linked_account_email' => null,
                    'detected_from' => trim($sourceMember->first_name.' '.$sourceMember->last_name),
                    'source_account_holder' => trim($linkedClient->first_name.' '.$linkedClient->last_name),
                ]);

                $suggestedSignatures[$candidateSignature] = true;
            }
        }

        return $suggestions->values();
    }

    protected function resolveLinkedClientHousehold(int $userId): ?Client
    {
        return Client::query()
            ->with([
                'familyMembers' => function ($query) {
                    $query->whereNull('beneficiary_profile_id')
                        ->whereNull('application_id')
                        ->orderBy('id');
                },
                'user',
            ])
            ->where('user_id', $userId)
            ->whereHas('familyMembers', function ($query) {
                $query->whereNull('beneficiary_profile_id')
                    ->whereNull('application_id');
            })
            ->orderByDesc('id')
            ->first();
    }

    protected function inferSuggestedRelationship(string $sourceRelation, string $candidateRelation): ?string
    {
        $sourceRelation = Str::lower(trim($sourceRelation));
        $candidateRelation = Str::lower(trim($candidateRelation));

        if (in_array($sourceRelation, ['mother', 'father'], true)) {
            return match ($candidateRelation) {
                'spouse' => $sourceRelation === 'mother' ? 'Father' : 'Mother',
                'child', 'son', 'daughter' => 'Sibling',
                'mother', 'father', 'guardian', 'relative', 'other' => 'Relative',
                default => null,
            };
        }

        if ($sourceRelation === 'spouse') {
            return match ($candidateRelation) {
                'child' => 'Child',
                'son' => 'Son',
                'daughter' => 'Daughter',
                'mother', 'father', 'sibling', 'relative', 'guardian', 'other' => 'Relative',
                default => null,
            };
        }

        if (in_array($sourceRelation, ['sibling', 'brother', 'sister'], true)) {
            return match ($candidateRelation) {
                'mother' => 'Mother',
                'father' => 'Father',
                'child' => 'Sibling',
                'son' => 'Brother',
                'daughter' => 'Sister',
                'guardian' => 'Guardian',
                'relative', 'other', 'spouse', 'partner', 'brother', 'sister', 'sibling' => 'Relative',
                default => null,
            };
        }

        if (in_array($sourceRelation, ['child', 'son', 'daughter'], true)) {
            return match ($candidateRelation) {
                'mother', 'father', 'guardian', 'sibling', 'brother', 'sister', 'relative', 'other' => 'Relative',
                default => null,
            };
        }

        if (in_array($sourceRelation, ['guardian', 'relative', 'other'], true)) {
            return match ($candidateRelation) {
                'mother' => 'Mother',
                'father' => 'Father',
                'guardian' => 'Guardian',
                'sibling' => 'Sibling',
                'child', 'spouse', 'relative', 'other' => 'Relative',
                default => null,
            };
        }

        return null;
    }

    protected function identitySignature(
        ?string $lastName,
        ?string $firstName,
        ?string $middleName,
        ?string $extensionName,
        $birthdate
    ): string {
        return implode('|', [
            Str::lower(trim((string) $lastName)),
            Str::lower(trim((string) $firstName)),
            Str::lower(trim((string) ($middleName ?? ''))),
            Str::lower(trim((string) ($extensionName ?? ''))),
            (string) $birthdate,
        ]);
    }

    protected function findPossibleFamilyAccountMatches(Client $client): array
    {
        $members = $client->familyMembers
            ->whereNull('beneficiary_profile_id')
            ->whereNull('application_id')
            ->values();

        if ($members->isEmpty()) {
            return [];
        }

        $possibleMatches = [];

        foreach ($members as $member) {
            if ($member->linked_user_id) {
                continue;
            }

            $query = User::query()
                ->where('role', 'client')
                ->whereRaw('LOWER(last_name) = ?', [Str::lower(trim((string) $member->last_name))])
                ->whereRaw('LOWER(first_name) = ?', [Str::lower(trim((string) $member->first_name))])
                ->whereRaw('LOWER(COALESCE(middle_name, \'\')) = ?', [Str::lower(trim((string) ($member->middle_name ?? '')))])
                ->whereRaw('LOWER(COALESCE(extension_name, \'\')) = ?', [Str::lower(trim((string) ($member->extension_name ?? '')))]);

            $candidate = $query->first();

            if (! $candidate) {
                continue;
            }

            $possibleMatches[$member->id] = [
                'user_id' => $candidate->id,
                'birthdate_matches' => (string) $candidate->birthdate === (string) $member->birthdate,
            ];
        }

        return $possibleMatches;
    }
}
