<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Certificate of Eligibility</title>
<style>
    :root{
        --ink:#111;
        --line:#111;
        --muted:#444;
        --paper:#fff;
    }
    *{
        box-sizing:border-box;
    }
    body{
        margin:0;
        background:#f3f4f6;
        color:var(--ink);
        font-family:"Times New Roman", serif;
    }
    .page{
        width:210mm;
        min-height:297mm;
        margin:14px auto;
        background:var(--paper);
        padding:12mm 12mm 14mm;
        box-shadow:0 8px 24px rgba(0,0,0,.08);
    }
    .print-btn{
        text-align:center;
        margin:24px 0;
    }
    .print-btn button{
        padding:10px 18px;
        border:0;
        border-radius:8px;
        background:#234E70;
        color:#fff;
        font:600 14px Arial, sans-serif;
        cursor:pointer;
    }
    .header{
        text-align:center;
        margin-bottom:8px;
        line-height:1.2;
    }
    .header .agency{
        font-size:16px;
        letter-spacing:.4px;
    }
    .header .title{
        font-size:26px;
        font-weight:700;
        letter-spacing:.8px;
        margin-top:8px;
        text-transform:uppercase;
    }
    .meta-grid{
        display:grid;
        grid-template-columns:1.2fr 1.1fr 1fr;
        gap:8px;
        margin:10px 0 8px;
        font-size:13px;
    }
    .meta-item{
        display:flex;
        align-items:flex-end;
        gap:6px;
        min-width:0;
    }
    .label{
        white-space:nowrap;
        font-weight:700;
    }
    .fill{
        flex:1;
        min-height:18px;
        border-bottom:1px solid var(--line);
        padding:0 3px 1px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .lead{
        margin:8px 0 10px;
        font-size:12.5px;
        line-height:1.35;
        text-align:justify;
    }
    .section{
        border:1px solid var(--line);
        margin-top:8px;
    }
    .section-title{
        border-bottom:1px solid var(--line);
        padding:4px 6px;
        font-size:12px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.4px;
    }
    .section-body{
        padding:6px;
        font-size:12.5px;
    }
    .name-grid{
        display:grid;
        grid-template-columns:1.4fr 1.4fr 1.4fr .8fr .7fr .6fr;
        gap:6px;
        margin-top:4px;
    }
    .field-block{
        min-width:0;
    }
    .field-block .field-label{
        font-size:10px;
        text-transform:uppercase;
        margin-top:3px;
        color:var(--muted);
    }
    .field-block .field-value{
        border-bottom:1px solid var(--line);
        min-height:18px;
        padding:0 3px 1px;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .inline-sentence{
        line-height:1.5;
        text-align:justify;
    }
    .inline-fill{
        display:inline-block;
        vertical-align:baseline;
        min-width:120px;
        border-bottom:1px solid var(--line);
        padding:0 4px 1px;
        text-align:center;
        font-weight:700;
    }
    .inline-fill.address{
        min-width:300px;
        text-align:left;
    }
    .inline-fill.amount{
        min-width:180px;
    }
    .inline-fill.short{
        min-width:90px;
    }
    .double-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:8px;
        margin-top:8px;
    }
    .assist-box{
        border:1px solid var(--line);
        padding:6px;
        min-height:124px;
    }
    .assist-box .box-title{
        font-size:11px;
        font-weight:700;
        margin-bottom:4px;
        text-transform:uppercase;
    }
    .assist-box .row{
        margin-top:5px;
    }
    .doc-grid{
        display:grid;
        grid-template-columns:repeat(3, 1fr);
        gap:4px 14px;
        margin-top:4px;
        font-size:11px;
    }
    .doc-item{
        display:flex;
        align-items:flex-start;
        gap:6px;
    }
    .doc-mark{
        width:13px;
        text-align:center;
        font-weight:700;
    }
    .sign-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:18px;
        margin-top:14px;
    }
    .sign-box{
        text-align:center;
    }
    .sign-line{
        border-bottom:1px solid var(--line);
        min-height:28px;
        padding-top:8px;
        font-weight:700;
    }
    .sign-caption{
        font-size:11px;
        margin-top:4px;
    }
    .receipt{
        margin-top:14px;
        border-top:1px solid var(--line);
        padding-top:8px;
    }
    .receipt-title{
        font-size:14px;
        font-weight:700;
        text-align:center;
        margin-bottom:8px;
        text-transform:uppercase;
    }
    .footer{
        margin-top:14px;
        font-size:10px;
        text-align:center;
        line-height:1.4;
    }
    @media print{
        body{
            background:#fff;
        }
        .page{
            width:auto;
            min-height:auto;
            margin:0;
            padding:8mm 10mm 10mm;
            box-shadow:none;
        }
        .print-btn{
            display:none;
        }
        @page{
            size:A4 portrait;
            margin:8mm;
        }
    }
</style>
</head>
<body>
@php
    use Carbon\Carbon;
    use Illuminate\Support\Number;
    use Illuminate\Support\Str;

    $client = $application->client;
    $beneficiary = $application->beneficiary;
    $typeOfAssistanceLabel = trim(implode(' - ', array_filter([
        $application->assistanceType?->name,
        $application->assistanceSubtype?->name,
    ])));
    $purposeLabel = trim(implode(' - ', array_filter([
        $application->assistanceDetail?->name,
    ])));
    $additionalTypeLabels = $application->assistanceRecommendations->map(function ($recommendation) {
        return trim(implode(' - ', array_filter([
            $recommendation->assistanceType?->name,
            $recommendation->assistanceSubtype?->name,
        ])));
    })->filter();
    $additionalPurposeLabels = $application->assistanceRecommendations->map(function ($recommendation) {
        return trim(implode(' - ', array_filter([
            $recommendation->assistanceDetail?->name,
        ])));
    })->filter();
    $typeOfAssistanceLabel = collect([$typeOfAssistanceLabel])
        ->merge($additionalTypeLabels)
        ->filter()
        ->unique()
        ->values()
        ->implode('; ');
    $purposeLabel = collect([$purposeLabel])
        ->merge($additionalPurposeLabels)
        ->filter()
        ->unique()
        ->values()
        ->implode('; ');
    $purpose = $purposeLabel ?: ($application->problem_statement ?: ($application->crisis_type ?: 'financial assistance'));
    $recommendationTotal = $application->assistanceRecommendations->sum(fn ($recommendation) => (float) $recommendation->final_amount);
    $amount = $application->assistanceRecommendations->isNotEmpty()
        ? $recommendationTotal
        : (float) ($application->final_amount ?? $application->recommended_amount ?? 0);
    $amountFormatted = 'PhP '.number_format($amount, 2);
    $amountWhole = (int) round($amount);
    $amountWords = $amountWhole > 0
        ? Str::upper(Number::spell($amountWhole)).' PESOS'
        : 'N/A';
    $modeOfAssistance = Str::lower((string) $application->mode_of_assistance);
    $isOutrightCash = $modeOfAssistance === 'cash';
    $isGuaranteeLetter = in_array($modeOfAssistance, ['gl', 'guarantee letter'], true);
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
    $servedName = $isGuaranteeLetter
        ? ($application->serviceProvider?->name ?: ($beneficiaryName !== '' ? $beneficiaryName : $clientName))
        : ($beneficiaryName !== '' ? $beneficiaryName : $clientName);
    $relationship = $beneficiary?->relationshipData?->name ?: 'Self';
    $clientBirthdate = $client?->birthdate ? Carbon::parse($client->birthdate) : null;
    $age = $clientBirthdate?->age;
    $certificateDate = $application->updated_at ?? now();
    $socialWorkerName = trim(implode(' ', array_filter([
        $application->socialWorker?->first_name,
        $application->socialWorker?->middle_name,
        $application->socialWorker?->last_name,
        $application->socialWorker?->extension_name,
    ]))) ?: ($application->socialWorker?->name ?? 'Social Worker');
    $approvingOfficerName = trim(implode(' ', array_filter([
        $application->approvingOfficer?->first_name,
        $application->approvingOfficer?->middle_name,
        $application->approvingOfficer?->last_name,
        $application->approvingOfficer?->extension_name,
    ]))) ?: ($application->approvingOfficer?->name ?? '');
    $socialWorkerSignature = $application->socialWorker?->signatureDataUrl();
    $approvingOfficerSignature = $application->approvingOfficer?->signatureDataUrl();
    $clientSignature = $application->clientSignatureDataUrl();
    $documentText = $application->documents
        ->map(fn ($doc) => Str::lower(trim(($doc->file_name ?? '').' '.($doc->remarks ?? ''))))
        ->implode(' | ');
    $hasDoc = function (array $keywords) use ($documentText) {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($documentText, Str::lower($keyword))) {
                return true;
            }
        }
        return false;
    };
    $documentChecklist = [
        ['General Intake Sheet', ['general intake', 'intake']],
        ['Medical Certificate / Abstract', ['medical certificate', 'medical abstract']],
        ['Laboratory Request', ['laboratory request', 'lab request']],
        ['Contract of Employment', ['contract of employment']],
        ['Justification', ['justification']],
        ['Prescriptions', ['prescription']],
        ['Promissory Note / Certificate of Balance', ['promissory note', 'certificate of balance']],
        ['Certificate of Employment', ['certificate of employment']],
        ['Valid I.D. Presented', ['valid id', 'valid i.d', 'id presented']],
        ['Statement of Account', ['statement of account']],
        ['Certificate of Attestation', ['certificate of attestation']],
        ['Treatment Protocol', ['treatment protocol']],
        ['Funeral Contract', ['funeral contract']],
        ['Income Tax Return', ['income tax return', 'itr']],
        ['Quotation / Charge Slip', ['quotation', 'charge slip']],
        ['Transfer Permit', ['transfer permit']],
        ['Death Certificate', ['death certificate']],
        ['Social Case Study Report', ['social case study report']],
        ['Case Summary Report', ['case summary report']],
        ['Referral Letter', ['referral letter']],
        ['Discharge Summary', ['discharge summary']],
        ['Death Summary', ['death summary']],
        ['Others', ['other']],
    ];
@endphp

<div class="print-btn">
    <button onclick="window.print()">Print Certificate</button>
</div>

<div class="page">
    <div class="header">
        <div class="agency">Republic of the Philippines</div>
        <div class="agency"><strong>DEPARTMENT OF SOCIAL WELFARE AND DEVELOPMENT</strong></div>
        <div class="title">Certificate of Eligibility</div>
    </div>

    <div class="meta-grid">
        <div class="meta-item">
            <span class="label">QN:</span>
            <span class="fill">{{ $application->reference_no ?? 'N/A' }}</span>
        </div>
        <div class="meta-item">
            <span class="label">PCN:</span>
            <span class="fill">{{ $application->reference_no ?? 'N/A' }}</span>
        </div>
        <div class="meta-item">
            <span class="label">Date:</span>
            <span class="fill">{{ $certificateDate->format('m d Y') }}</span>
        </div>
        <div class="meta-item">
            <span class="label">Birthday:</span>
            <span class="fill">{{ $clientBirthdate?->format('m d Y') ?? 'N/A' }}</span>
        </div>
        <div class="meta-item">
            <span class="label">GL No.:</span>
            <span class="fill">{{ $application->reference_no ?? 'N/A' }}</span>
        </div>
        <div class="meta-item">
            <span class="label">Type of assistance:</span>
            <span class="fill">{{ $typeOfAssistanceLabel !== '' ? $typeOfAssistanceLabel : 'AICS Assistance' }}</span>
        </div>
    </div>

    <p class="lead">
        Records of the case such as the following are confidentially filed at the Crisis Intervention Program (CIP).
    </p>

    <div class="section">
        <div class="section-title">Client Information</div>
        <div class="section-body">
            <div class="inline-sentence">
                This is to certify that
                <span class="inline-fill">{{ $clientName ?: 'N/A' }}</span>
                and presently residing at
                <span class="inline-fill address">{{ $client?->full_address ?: 'N/A' }}</span>.
            </div>

            <div class="name-grid">
                <div class="field-block">
                    <div class="field-value">{{ $client?->first_name ?: 'N/A' }}</div>
                    <div class="field-label">First Name</div>
                </div>
                <div class="field-block">
                    <div class="field-value">{{ $client?->middle_name ?: 'N/A' }}</div>
                    <div class="field-label">Middle Name</div>
                </div>
                <div class="field-block">
                    <div class="field-value">{{ $client?->last_name ?: 'N/A' }}</div>
                    <div class="field-label">Last Name</div>
                </div>
                <div class="field-block">
                    <div class="field-value">{{ $client?->extension_name ?: '-' }}</div>
                    <div class="field-label">Ext.</div>
                </div>
                <div class="field-block">
                    <div class="field-value">{{ $client?->sex ?: 'N/A' }}</div>
                    <div class="field-label">Sex</div>
                </div>
                <div class="field-block">
                    <div class="field-value">{{ $age !== null ? $age : 'N/A' }}</div>
                    <div class="field-label">Age</div>
                </div>
            </div>

            <div class="inline-sentence" style="margin-top:10px;">
                The client has been found eligible for assistance after the assessment and validation conducted,
                {{ $beneficiaryName !== '' ? 'for representation of his/her' : 'for himself/herself.' }}
                @if($beneficiaryName !== '')
                    <span class="inline-fill short">{{ $relationship }}</span>
                    <span class="inline-fill">{{ $beneficiaryName }}</span>.
                @endif
            </div>
        </div>
    </div>

    <div class="double-grid">
        <div class="assist-box">
            <div class="box-title">If Outright Cash</div>
            <div class="row">
                Type of assistance:
                <span class="inline-fill">{{ $isOutrightCash && $typeOfAssistanceLabel !== '' ? $typeOfAssistanceLabel : '' }}</span>
            </div>
            <div class="row">
                Amount in words:
                <span class="inline-fill amount">{{ $isOutrightCash ? $amountWords : '' }}</span>
            </div>
            <div class="row">
                Amount in figures:
                <span class="inline-fill short">{{ $isOutrightCash ? $amountFormatted : '' }}</span>
            </div>
            <div class="row">
                Purpose of assistance:
                <span class="inline-fill amount">{{ $isOutrightCash ? $purpose : '' }}</span>
            </div>
        </div>

        <div class="assist-box">
            <div class="box-title">If Guarantee Letter</div>
            <div class="row">
                Type of assistance:
                <span class="inline-fill">{{ $isGuaranteeLetter && $typeOfAssistanceLabel !== '' ? $typeOfAssistanceLabel : '' }}</span>
            </div>
            <div class="row">
                Amount in words:
                <span class="inline-fill amount">{{ $isGuaranteeLetter ? $amountWords : '' }}</span>
            </div>
            <div class="row">
                Amount in figures:
                <span class="inline-fill short">{{ $isGuaranteeLetter ? $amountFormatted : '' }}</span>
            </div>
            <div class="row">
                Payable to:
                <span class="inline-fill">{{ $isGuaranteeLetter ? ($servedName ?: 'N/A') : '' }}</span>
            </div>
            <div class="row">
                Purpose of assistance:
                <span class="inline-fill amount">{{ $isGuaranteeLetter ? $purpose : '' }}</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Records on File</div>
        <div class="section-body">
            <div class="doc-grid">
                @foreach($documentChecklist as [$label, $keywords])
                    <div class="doc-item">
                        <span class="doc-mark">{{ $hasDoc($keywords) ? '/' : '[ ]' }}</span>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="sign-grid">
        <div class="sign-box">
            @if($socialWorkerSignature)
                <img src="{{ $socialWorkerSignature }}" alt="Social worker signature" class="signature-image">
            @endif
            <div class="sign-line">{{ $socialWorkerName }}</div>
            <div class="sign-caption">Prepared and certified by / Social Worker</div>
        </div>
        <div class="sign-box">
            @if($approvingOfficerSignature)
                <img src="{{ $approvingOfficerSignature }}" alt="Approving officer signature" class="signature-image">
            @endif
            <div class="sign-line">{{ $approvingOfficerName ?: "\u{00A0}" }}</div>
            <div class="sign-caption">Approved by / Approving Authority</div>
        </div>
    </div>

    <div class="receipt">
        <div class="receipt-title">Acknowledgement Receipt</div>
        <div class="inline-sentence">
            I acknowledge receipt of assistance in the amount of
            <span class="inline-fill amount">{{ $amountFormatted }}</span>.
        </div>
        <div class="sign-grid" style="margin-top:18px;">
            <div class="sign-box">
                @if($clientSignature)
                    <img src="{{ $clientSignature }}" alt="Client signature" class="signature-image">
                @endif
                <div class="sign-line">{{ $servedName ?: '&nbsp;' }}</div>
                <div class="sign-caption">Received by (Signature over Printed Name)</div>
            </div>
            <div class="sign-box">
                <div class="sign-line">&nbsp;</div>
                <div class="sign-caption">License Number</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Website: http://www.dswd.gov.ph
        &nbsp;&nbsp; Tel Nos.: _______________
        &nbsp;&nbsp; Telefax: _______________
        <br>
        DSWD Central/Field Office, ________________________________, Philippines
    </div>
</div>
</body>
</html>
    .signature-image{
        display:block;
        margin:0 auto 4px;
        max-height:42px;
        max-width:180px;
        object-fit:contain;
    }
