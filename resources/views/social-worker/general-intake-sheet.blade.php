<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>General Intake Sheet</title>
<style>
    *{box-sizing:border-box}
    body{margin:0;background:#f3f4f6;color:#111;font-family:Arial, sans-serif}
    .page{width:210mm;min-height:297mm;margin:14px auto;background:#fff;padding:8mm 9mm;box-shadow:0 8px 24px rgba(0,0,0,.08);position:relative}
    .print-btn{text-align:center;margin:24px 0}
    .print-btn button{padding:10px 18px;border:0;border-radius:8px;background:#234E70;color:#fff;font:600 14px Arial,sans-serif;cursor:pointer}
    .top-title{text-align:center;font-weight:800;font-size:18px;letter-spacing:.5px;margin-bottom:6px}
    .header-grid{display:grid;grid-template-columns:1.3fr 1fr 1fr;gap:5px;font-size:10.5px;margin-bottom:6px}
    .box{border:1px solid #111;padding:4px}
    .section{border:1px solid #111;margin-top:5px}
    .section-title{background:#eef2f7;border-bottom:1px solid #111;padding:3px 5px;font-weight:800;font-size:10.5px;text-transform:uppercase}
    .section-body{padding:5px;font-size:10.5px}
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:5px}
    .grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:5px}
    .grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:5px}
    .field{display:flex;gap:4px;align-items:flex-end;min-width:0;line-height:1.25}
    .label{font-weight:700;white-space:nowrap}
    .line{border-bottom:1px solid #111;min-height:15px;flex:1;padding:0 2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .line.block{white-space:normal;min-height:32px}
    .mini-label{font-size:8.5px;color:#333;text-transform:uppercase;margin-top:2px}
    .name-grid{display:grid;grid-template-columns:1.3fr 1.3fr 1.3fr .55fr;gap:4px}
    .name-cell .line{display:block;width:100%}
    .check-list{display:grid;grid-template-columns:1fr 1fr;gap:3px 8px}
    .check-list.three{grid-template-columns:1fr 1fr 1fr}
    .check{display:flex;gap:4px;align-items:flex-start;line-height:1.2}
    .mark{width:18px;font-weight:700}
    table{width:100%;border-collapse:collapse;font-size:10.5px}
    th,td{border:1px solid #111;padding:3px 4px;vertical-align:top}
    th{background:#eef2f7;text-align:left}
    .signature-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-top:12px;font-size:10.5px}
    .signature{text-align:center}
    .signature-line{border-bottom:1px solid #111;height:26px;padding-top:8px;font-weight:700}
    .footer{position:absolute;left:9mm;right:9mm;bottom:5mm;text-align:center;font-size:8.5px;line-height:1.3}
    .page-no{position:absolute;right:9mm;bottom:5mm;font-size:9px;font-weight:700}
    .muted{color:#333}
    @media print{
        body{background:#fff}
        .page{width:auto;min-height:277mm;margin:0;box-shadow:none;page-break-after:always}
        .page:last-child{page-break-after:auto}
        .print-btn{display:none}
        @page{size:A4 portrait;margin:8mm}
    }
</style>
</head>
<body>
@php
    use Carbon\Carbon;

    $client = $application->client;
    $beneficiary = $application->beneficiary;
    $householdMembers = $application->familyMembers;
    $clientName = trim(implode(' ', array_filter([
        $client?->first_name,
        $client?->middle_name,
        $client?->last_name,
        $client?->extension_name,
    ])));
    $beneficiaryName = trim(implode(' ', array_filter([
        $beneficiary?->first_name,
        $beneficiary?->middle_name,
        $beneficiary?->last_name,
        $beneficiary?->extension_name,
    ])));
    $fullNameParts = function ($person) {
        return [
            $person?->last_name ?: '',
            $person?->first_name ?: '',
            $person?->middle_name ?: '',
            $person?->extension_name ?: '',
        ];
    };
    $clientParts = $fullNameParts($client);
    $beneficiaryParts = $fullNameParts($beneficiary);
    $amountNeeded = (float) ($application->amount_needed ?? 0);
    $purpose = $application->problem_statement ?: $application->crisis_type ?: ($application->assistanceSubtype?->name ?? '');
    $modeName = $application->modeOfAssistance?->name ?? $application->mode_of_assistance;
    $visitOptions = \App\Models\ServicePoint::where('is_active', true)
        ->orderByRaw("CASE name
            WHEN 'Online' THEN 1
            WHEN 'Onsite' THEN 2
            WHEN 'Offsite' THEN 3
            WHEN 'Malasakit Center' THEN 4
            ELSE 99
        END")
        ->orderBy('name')
        ->pluck('name')
        ->all();
    $clientTypeOptions = \App\Models\ClientType::where('is_active', true)
        ->orderByRaw("CASE
            WHEN LOWER(name) = 'new' THEN 1
            WHEN LOWER(name) = 'new walk-in' THEN 2
            WHEN LOWER(name) = 'returning' THEN 3
            WHEN LOWER(name) = 'referral' THEN 4
            ELSE 99
        END")
        ->orderBy('name')
        ->pluck('name')
        ->all();
    if ($application->gis_client_type && ! in_array($application->gis_client_type, $clientTypeOptions, true)) {
        array_unshift($clientTypeOptions, $application->gis_client_type);
    }
    $modeOptions = ['Outright Cash', 'Guarantee Letter', 'Material Assistance', 'Psychosocial Support', 'Referral Service'];
    $primaryAssistance = trim(implode(' - ', array_filter([
        $application->assistanceType?->name,
        $application->assistanceSubtype?->name,
        $application->assistanceDetail?->name,
    ])));
    $check = fn ($selected, $value) => $selected === $value ? '[x]' : '[ ]';
    $contains = function ($items, $needle) {
        return in_array($needle, $items ?? [], true);
    };
    $fmtMoney = fn ($value) => filled($value) ? 'PhP '.number_format((float) $value, 2) : '';
    $incomeSources = collect($application->income_sources ?? []);
    $incomeAmount = function ($source) use ($incomeSources) {
        $row = $incomeSources->firstWhere('source', $source);
        return isset($row['amount']) && $row['amount'] !== '' ? number_format((float) $row['amount'], 2) : '';
    };
    $socialWorkerName = trim(implode(' ', array_filter([
        $application->socialWorker?->first_name,
        $application->socialWorker?->middle_name,
        $application->socialWorker?->last_name,
        $application->socialWorker?->extension_name,
    ]))) ?: ($application->socialWorker?->name ?? 'Social Worker');
    $date = $application->updated_at ?? now();
    $recommendations = $application->assistanceRecommendations->isNotEmpty()
        ? $application->assistanceRecommendations
        : collect([
            (object) [
                'assistanceType' => $application->assistanceType,
                'assistanceSubtype' => $application->assistanceSubtype,
                'assistanceDetail' => $application->assistanceDetail,
                'final_amount' => $application->final_amount,
                'notes' => $application->problem_statement,
                'referralInstitution' => null,
            ],
        ]);
@endphp

<div class="print-btn">
    <button onclick="window.print()">Print General Intake Sheet</button>
</div>

<div class="page">
    <div class="top-title">GENERAL INTAKE SHEET</div>

    <div class="header-grid">
        <div class="box">
            @foreach($visitOptions as $option)
                <span>{{ $check($application->gis_visit_type, $option) }} {{ $option }}</span><br>
            @endforeach
        </div>
        <div class="box">
            <div class="field"><span class="label">Date:</span><span class="line">{{ $date ? Carbon::parse($date)->format('m / d / Y') : '' }}</span></div>
            <div class="field"><span class="label">Reference:</span><span class="line">{{ $application->reference_no }}</span></div>
        </div>
        <div class="box">
            @foreach($clientTypeOptions as $clientTypeOption)
                <div>{{ $check($application->gis_client_type, $clientTypeOption) }} {{ $clientTypeOption }}</div>
            @endforeach
        </div>
    </div>

    <div class="section">
        <div class="section-title">Client's Name</div>
        <div class="section-body name-grid">
            @foreach(['Last Name', 'First Name', 'Middle Name', 'Ext.'] as $i => $label)
                <div class="name-cell"><div class="line">{{ $clientParts[$i] }}</div><div class="mini-label">{{ $label }}</div></div>
            @endforeach
        </div>
    </div>

    <div class="section">
        <div class="section-title">Beneficiary's Name</div>
        <div class="section-body name-grid">
            @foreach(['Last Name', 'First Name', 'Middle Name', 'Ext.'] as $i => $label)
                <div class="name-cell"><div class="line">{{ $beneficiaryParts[$i] }}</div><div class="mini-label">{{ $label }}</div></div>
            @endforeach
        </div>
    </div>

    <div class="section">
        <div class="section-body grid-2">
            <div class="field"><span class="label">Mode of Assistance:</span><span class="line">{{ $modeName }}</span></div>
            <div class="field"><span class="label">Amount Needed:</span><span class="line">{{ $fmtMoney($amountNeeded) }}</span></div>
            <div class="field"><span class="label">Purpose of Assistance:</span><span class="line">{{ $purpose }}</span></div>
            <div class="field"><span class="label">Diagnosis/Cause of Death:</span><span class="line">{{ $application->diagnosis_or_cause_of_death }}</span></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">I. Income and Financial Resources</div>
        <div class="section-body grid-2">
            <div class="field"><span class="label">Employed family members:</span><span class="line">{{ $application->working_members }}</span></div>
            <div class="field"><span class="label">Seasonal employee members:</span><span class="line">{{ $application->seasonal_worker_members }}</span></div>
            <div class="field"><span class="label">Combined family income:</span><span class="line">{{ $fmtMoney($application->monthly_income) }}</span></div>
            <div class="field"><span class="label">Household members:</span><span class="line">{{ $application->household_members }}</span></div>
            <div class="field"><span class="label">Insurance coverage:</span><span class="line">{{ $application->has_insurance_coverage ? 'Yes' : 'No' }}</span></div>
            <div class="field"><span class="label">Savings:</span><span class="line">{{ $application->has_savings ? 'Yes' : 'No' }}</span></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">II. Budget and Expenses</div>
        <div class="section-body grid-2">
            <div class="field"><span class="label">Monthly expenses of the family:</span><span class="line">{{ $fmtMoney($application->monthly_expenses) }}</span></div>
            <div class="field"><span class="label">Availability of emergency fund:</span><span class="line">{{ $application->emergency_fund }}</span></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">III. Severity of the Crisis</div>
        <div class="section-body">
            <div class="grid-2">
                <div class="field"><span class="label">Disease duration:</span><span class="line">{{ $application->disease_duration }}</span></div>
                <div class="field"><span class="label">Experienced crisis in past 3 months:</span><span class="line">{{ is_null($application->experienced_recent_crisis) ? '' : ($application->experienced_recent_crisis ? 'YES' : 'NO') }}</span></div>
            </div>
            <div class="check-list three" style="margin-top:5px">
                @foreach(['Hospitalization', 'Death of a family member', 'Catastrophic Event (fire, earthquake, flooding, etc.)', 'Disablement', 'Inability to secure stable employment', 'Loss of Livelihood', 'Others'] as $item)
                    <div class="check"><span class="mark">{{ $contains($application->recent_crisis_types, $item) ? '[x]' : '[ ]' }}</span><span>{{ $item }}</span></div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">IV. Availability of Support Systems</div>
        <div class="section-body check-list three">
            @foreach(['Family', 'Friend/s', 'Employed Relatives', 'Employer', 'Seasonal Employee', 'Church/Community Organization', 'Not applicable'] as $item)
                <div class="check"><span class="mark">{{ $contains($application->support_systems, $item) ? '[x]' : '[ ]' }}</span><span>{{ $item }}</span></div>
            @endforeach
        </div>
    </div>

    <div class="section">
        <div class="section-title">V. External Resources Tapped by the Family</div>
        <div class="section-body check-list three">
            @foreach(['Health Card', 'Guarantee Letter from other agencies', 'MSS Discount', 'Senior Citizen Discount', 'PWD Discount', 'PhilHealth', 'Others'] as $item)
                <div class="check"><span class="mark">{{ $contains($application->external_resources, $item) ? '[x]' : '[ ]' }}</span><span>{{ $item }}</span></div>
            @endforeach
        </div>
    </div>

    <div class="section">
        <div class="section-title">VI. Self-Help and Client Efforts</div>
        <div class="section-body check-list">
            @foreach(['Successfully sought employment opportunities or explored additional income sources', 'Successfully reached out to relevant organizations or agencies for financial assistance or support'] as $item)
                <div class="check"><span class="mark">{{ $contains($application->self_help_efforts, $item) ? '[x]' : '[ ]' }}</span><span>{{ $item }}</span></div>
            @endforeach
        </div>
    </div>

    <div class="section">
        <div class="section-title">VII. Vulnerability and Risk Factors</div>
        <div class="section-body check-list">
            <div class="check"><span class="mark">{{ $application->has_vulnerable_household_member ? '[x]' : '[ ]' }}</span><span>There are elderly/ Child in need/ PWD/ Pregnant in the household</span></div>
            <div class="check"><span class="mark">{{ $application->earner_unable_to_work ? '[x]' : '[ ]' }}</span><span>A member is physically or mentally incapacitated to work</span></div>
            <div class="check"><span class="mark">{{ $application->has_unstable_employment ? '[x]' : '[ ]' }}</span><span>Inability to secure stable employment</span></div>
        </div>
    </div>

    <div class="footer">DSWD Central/Field Office, ____________________ (address), Philippines (Zip Code) &nbsp; Website: http://www.dswd.gov.ph &nbsp; Tel Nos.: ________________ &nbsp; Telefax: _______________</div>
    <div class="page-no">PAGE 1 of 2</div>
</div>

<div class="page">
    <div class="section">
        <div class="section-title">VIII. Client Sector</div>
        <div class="section-body grid-3">
            <div class="field"><span class="label">Target Sector:</span><span class="line">{{ collect($application->client_sectors ?? [$application->client_sector])->filter()->implode(', ') }}</span></div>
            <div class="field"><span class="label">Sub-Category:</span><span class="line">{{ collect($application->client_sub_categories ?? [$application->client_sub_category])->filter()->implode(', ') }}</span></div>
            <div class="field"><span class="label">Type of Disability:</span><span class="line">{{ collect($application->disability_types ?? [$application->disability_type])->filter()->implode(', ') }}</span></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">IX. Problem Presented</div>
        <div class="section-body"><div class="line block">{{ $application->problem_statement }}</div></div>
    </div>

    <div class="section">
        <div class="section-title">X. Social Worker's Assessment</div>
        <div class="section-body"><div class="line block">{{ $application->social_worker_assessment }}</div></div>
    </div>

    <div class="section">
        <div class="section-title">Assistance Recommended</div>
        <div class="section-body">
            <div class="grid-4" style="margin-bottom:5px">
                @foreach($modeOptions as $option)
                    <div class="check"><span class="mark">{{ str_contains(strtolower($primaryAssistance.' '.$modeName), strtolower(str_replace(' Service', '', $option))) ? '[x]' : '[ ]' }}</span><span>{{ $option }}</span></div>
                @endforeach
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Purpose of Assistance</th>
                        <th>Amount</th>
                        <th>Fund Source</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recommendations->filter(fn ($rec) => $rec->assistanceType || $rec->referralInstitution) as $rec)
                        @php
                            $label = trim(implode(' - ', array_filter([
                                $rec->assistanceType?->name,
                                $rec->assistanceSubtype?->name,
                                $rec->assistanceDetail?->name,
                                $rec->referralInstitution?->name,
                            ])));
                        @endphp
                        <tr>
                            <td>{{ $label ?: $purpose }}</td>
                            <td>{{ ((float) ($rec->final_amount ?? 0)) > 0 ? $fmtMoney($rec->final_amount) : '' }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                    @for($i = $recommendations->count(); $i < 3; $i++)
                        <tr><td>&nbsp;</td><td></td><td></td></tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Total Income in the Past 6 Months</div>
        <div class="section-body">
            <table>
                <tbody>
                    @foreach(['Salaries/Wages from Employment', 'Entrepreneurial income/profits', 'Cash assistance from domestic source', 'Cash assistance from abroad', 'Transfers from the government (e.g. 4Ps)', 'Pension', 'Other income'] as $source)
                        <tr>
                            <td>{{ $source }}</td>
                            <td style="width:35%">{{ $incomeAmount($source) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <th>Total income in the past 6 months</th>
                        <th>{{ $fmtMoney($application->total_income_past_six_months) }}</th>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Family Composition</div>
        <div class="section-body">
            <table>
                <thead><tr><th>Name</th><th>Relationship</th><th>Birthdate</th><th>Occupation</th></tr></thead>
                <tbody>
                    @forelse($householdMembers->take(6) as $member)
                        <tr>
                            <td>{{ trim($member->first_name.' '.$member->middle_name.' '.$member->last_name.' '.$member->extension_name) }}</td>
                            <td>{{ $member->relationshipData->name ?? $member->relationship }}</td>
                            <td>{{ $member->birthdate }}</td>
                            <td>{{ $member->occupation ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">&nbsp;</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="signature-grid">
        <div class="signature">
            <div class="signature-line">{{ $socialWorkerName }}</div>
            <div>Interviewed by / Social Worker</div>
            <div class="muted">License no.: _____________</div>
        </div>
        <div class="signature">
            <div class="signature-line">&nbsp;</div>
            <div>Reviewed and Approved by / Approving Authority</div>
        </div>
    </div>

    <div class="footer">DSWD Central/Field Office, ____________________ (address), Philippines (Zip Code) &nbsp; Website: http://www.dswd.gov.ph &nbsp; Tel Nos.: ________________ &nbsp; Telefax: _______________</div>
    <div class="page-no">PAGE 2 of 2</div>
</div>
</body>
</html>
