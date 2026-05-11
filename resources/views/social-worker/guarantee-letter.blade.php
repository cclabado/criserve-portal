<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Guarantee Letter</title>
<style>
    *{box-sizing:border-box}
    body{margin:0;background:#f3f4f6;color:#111;font-family:"Times New Roman",serif}
    .page{width:210mm;min-height:297mm;margin:14px auto;background:#fff;padding:14mm 14mm 16mm;box-shadow:0 8px 24px rgba(0,0,0,.08)}
    .print-btn{text-align:center;margin:24px 0}
    .print-btn button{padding:10px 18px;border:0;border-radius:8px;background:#234E70;color:#fff;font:600 14px Arial,sans-serif;cursor:pointer}
    .meta{display:flex;justify-content:space-between;gap:16px;font-size:13px;margin-bottom:12px}
    .gl-no{font-weight:700}
    .date-line{text-align:right}
    .recipient{font-size:14px;line-height:1.45;margin-bottom:14px}
    .salutation{font-size:14px;margin:12px 0}
    .body-copy{font-size:14px;line-height:1.6;text-align:justify}
    .body-copy p{margin:0 0 12px}
    .body-copy ul{margin:8px 0 12px 22px;padding:0}
    .body-copy li{margin:0 0 6px}
    .signature-block{margin-top:28px;font-size:14px;line-height:1.5}
    .signature-image{display:block;max-height:48px;max-width:220px;object-fit:contain}
    .signature-name{margin-top:34px;font-weight:700;text-transform:uppercase}
    .signature-title{font-size:13px}
    .validity{margin-top:20px;font-size:13px}
    .validity strong{font-size:14px}
    .footnote{font-size:11.5px;color:#333}
    .receipt{margin-top:34px;border-top:1px solid #111;padding-top:16px}
    .receipt-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 28px;font-size:13px}
    .receipt-row{display:flex;gap:8px;align-items:flex-end}
    .receipt-label{min-width:112px;font-weight:700}
    .receipt-line{flex:1;border-bottom:1px solid #111;min-height:18px}
    .copy-tag{margin-top:8px;font-size:11px;text-transform:uppercase;letter-spacing:.18em;color:#555}
    @media print{
        body{background:#fff}
        .page{width:auto;min-height:277mm;margin:0;box-shadow:none;page-break-after:always}
        .page:last-child{page-break-after:auto}
        .print-btn{display:none}
        @page{size:A4 portrait;margin:10mm}
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
    $serviceProvider = $application->serviceProvider;
    $amount = (float) ($application->final_amount ?? $application->recommended_amount ?? $application->amount_needed ?? 0);
    $amountFormatted = 'Php'.number_format($amount, 2);
    $wholeAmount = (int) floor($amount);
    $amountWords = $amount > 0
        ? Str::title(Number::spell($wholeAmount)).' Pesos Only'
        : 'Zero Pesos Only';
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
    $beneficiaryReference = $beneficiaryName !== '' && strcasecmp($beneficiaryName, $clientName) !== 0
        ? ' for '.($beneficiary?->relationshipData?->name ? strtolower($beneficiary->relationshipData->name).' ' : '').$beneficiaryName
        : '';
    $letterDate = $application->updated_at ?? now();
    $validUntil = Carbon::parse($letterDate)->copy()->addDays(30);
    $socialWorkerName = trim(implode(' ', array_filter([
        $application->socialWorker?->first_name,
        $application->socialWorker?->middle_name,
        $application->socialWorker?->last_name,
        $application->socialWorker?->extension_name,
    ]))) ?: ($application->socialWorker?->name ?? 'Assigned Social Worker');
    $approvingOfficerSignature = $application->approvingOfficer?->signatureDataUrl();
    $clientSignature = $application->clientSignatureDataUrl();
    $approverName = trim(implode(' ', array_filter([
        $application->approvingOfficer?->first_name,
        $application->approvingOfficer?->middle_name,
        $application->approvingOfficer?->last_name,
        $application->approvingOfficer?->extension_name,
    ]))) ?: ($application->approvingOfficer?->name ?? 'Approving Officer');
    $purposeLabel = trim(implode(' - ', array_filter([
        $application->assistanceSubtype?->name,
        $application->assistanceDetail?->name,
    ]))) ?: ($application->problem_statement ?: 'assistance');
    $salutationName = $serviceProvider?->addressee ?: 'Sir/Madam';
@endphp

<div class="print-btn">
    <button onclick="window.print()">Print Guarantee Letter</button>
</div>

@for($copy = 0; $copy < 2; $copy++)
<div class="page">
    <div class="meta">
        <div class="gl-no">GL No. {{ $application->reference_no }}</div>
        <div class="date-line">{{ Carbon::parse($letterDate)->format('F d, Y') }}</div>
    </div>

    <div class="recipient">
        <div>{{ $serviceProvider?->addressee ?: 'To Whom It May Concern' }}</div>
        <div>{{ $serviceProvider?->name ?: 'Service Provider' }}</div>
        <div>{{ $serviceProvider?->address ?: 'Address not provided' }}</div>
    </div>

    <div class="salutation">Dear {{ $salutationName }}:</div>

    <div class="body-copy">
        <p>
            This has reference to the request for {{ $application->assistanceSubtype?->name ?: 'Assistance' }} of herein client
            <strong>{{ strtoupper($clientName ?: 'N/A') }}</strong>, from {{ $client?->full_address ?: 'address not provided' }}{{ $beneficiaryReference ? ',' : '' }}{{ $beneficiaryReference }}.
        </p>

        <p>
            The Department of Social Welfare and Development has assessed and validated the said request for assistance through the Crisis Intervention Program.
            Thus, the Department is issuing this letter to guarantee the payment of the {{ strtolower($purposeLabel) }} in the amount of
            <strong>{{ $amountWords }}</strong> ({{ $amountFormatted }}).
        </p>

        <p>
            To facilitate the payment, please submit to the Crisis Intervention Program through <strong>{{ $socialWorkerName }}</strong>
            the following documents for the preparation of Disbursement Voucher within one week after the service has been completed.
        </p>

        <ul>
            <li>Guarantee Letter (GL) from the DSWD with your company’s “received” stamp</li>
            <li>Statement of Account (SOA) or Billing Statement addressed to DSWD</li>
        </ul>

        <p>
            Please be informed that said payment will be directly deposited to your company’s bank account.
            Should there be any query, you may coordinate with the assigned social worker through CrIServe for follow-up.
        </p>

        <p>For your consideration.</p>
        <p>Thank you.</p>
    </div>

    <div class="signature-block">
        <div>Approved by:</div>
        @if($approvingOfficerSignature)
            <img src="{{ $approvingOfficerSignature }}" alt="Approving officer signature" class="signature-image">
        @endif
        <div class="signature-name">{{ strtoupper($approverName) }}</div>
        <div class="signature-title">Approving Officer</div>
    </div>

    <div class="validity">
        <strong>Valid until {{ $validUntil->format('F d, Y') }}</strong><br>
        <span class="footnote">*validity period includes the time of receipt of the guarantee letter by the service provider</span>
    </div>

    @if($copy === 1)
    <div class="receipt">
        <div class="copy-tag">Service Provider Receipt Copy</div>
        <div class="receipt-grid">
            <div class="receipt-row">
                <span class="receipt-label">RECEIVED BY:</span>
                <span class="receipt-line"></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">RELEASED BY:</span>
                <span class="receipt-line">{{ strtoupper($approverName) }}</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">FULL NAME:</span>
                <span class="receipt-line"></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">SIGNATURE:</span>
                <span class="receipt-line">
                    @if($clientSignature)
                        <img src="{{ $clientSignature }}" alt="Client signature" class="signature-image">
                    @endif
                </span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">CONTACT NO.:</span>
                <span class="receipt-line"></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">DATE / TIME:</span>
                <span class="receipt-line"></span>
            </div>
        </div>
    </div>
    @endif
</div>
@endfor
</body>
</html>
