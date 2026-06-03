<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>LDDAP-ADA {{ $lddapAdaNumber }}</title>
<style>
    *{box-sizing:border-box}
    body{margin:0;background:#f3f4f6;color:#111;font-family:"Times New Roman",serif}
    .page{width:297mm;min-height:210mm;margin:12px auto;background:#fff;padding:10mm 12mm;box-shadow:0 8px 24px rgba(0,0,0,.08)}
    .print-btn{text-align:center;margin:20px 0}
    .print-btn button{padding:10px 18px;border:0;border-radius:8px;background:#234E70;color:#fff;font:600 14px Arial,sans-serif;cursor:pointer}
    .header{text-align:center}
    .header .small{font-size:11px}
    .header .title{font-size:18px;font-weight:700;letter-spacing:.04em;margin-top:4px}
    .subline{font-size:11px;margin-top:2px}
    table{width:100%;border-collapse:collapse}
    .meta{margin-top:10px;font-size:12px}
    .meta td,.listing th,.listing td,.summary td{border:1px solid #111;padding:5px 6px;vertical-align:top}
    .meta .label{font-weight:700;width:14%}
    .note{margin-top:10px;font-size:12px;line-height:1.4}
    .listing{margin-top:10px;font-size:11px}
    .listing th{font-size:10px;text-transform:uppercase;text-align:center}
    .listing .num{text-align:right;white-space:nowrap}
    .summary{margin-top:0;font-size:11px}
    .summary td{font-weight:700}
    .signatures{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:14px}
    .sig-box{border:1px solid #111;padding:10px;min-height:110px;font-size:12px}
    .sig-box p{margin:0 0 18px}
    .sig-line{margin-top:28px;border-top:1px solid #111;padding-top:4px;font-weight:700}
    .muted{font-size:11px}
    .footnotes{margin-top:12px;font-size:10px;line-height:1.35}
    @media print{
        body{background:#fff}
        .page{width:auto;min-height:auto;margin:0;box-shadow:none}
        .print-btn{display:none}
        @page{size:A4 landscape;margin:8mm}
    }
</style>
</head>
<body>
<div class="print-btn"><button onclick="window.print()">Print LDDAP-ADA</button></div>
<div class="page">
    <div class="header">
        <div class="small">Republic of the Philippines</div>
        <div class="small">{{ $entityName }} - Financial Management Service</div>
        <div class="title">LIST OF DUE &amp; DEMANDABLE ACCOUNTS PAYABLE - ADVICE TO DEBIT ACCOUNTS (LDDAP-ADA)</div>
        <div class="subline">{{ $officeName }}</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">MDS Fund</td>
            <td>101</td>
            <td class="label">Servicing Bank</td>
            <td>{{ $servicingBankDisplay ?: '-' }}</td>
            <td class="label">MDS Sub-Account No.</td>
            <td>{{ data_get($batch ?? null, 'mds_sub_account_number') ?: ($application->gl_mds_sub_account_number ?: '-') }}</td>
        </tr>
        <tr>
            <td class="label">NCA No.</td>
            <td>{{ $ncaNumber }}</td>
            <td class="label">NCA Date</td>
            <td>{{ optional($ncaDate)->format('M d, Y') ?? '-' }}</td>
            <td class="label">LDDAP-ADA No.</td>
            <td>{{ $lddapAdaNumber ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Date of Issue</td>
            <td>{{ optional($lddapAdaDate)->format('M d, Y') ?? '-' }}</td>
            <td class="label">Reference No.</td>
            <td>{{ $documentBatchNo ?: $documentReferenceNumber }}</td>
            <td class="label">ORS No.</td>
            <td>{{ $orsNumber ?: '-' }}</td>
        </tr>
    </table>

    <div class="note">
        Please debit MDS Sub-Account Number <strong>{{ data_get($batch ?? null, 'mds_sub_account_number') ?: ($application->gl_mds_sub_account_number ?: '-') }}</strong> and credit the account of the creditor listed below to cover payment of due and demandable accounts payable.
        <br>
        <strong>Total Net Amount:</strong> {{ $totalAmountInWords }} (PHP {{ number_format($netAmount, 2) }})
    </div>

    <table class="listing">
        <thead>
            <tr>
                <th style="width:7%">Seq</th>
                <th style="width:11%">GL Ref.</th>
                <th style="width:19%">Name of Creditor</th>
                <th style="width:16%">Preferred Servicing Bank</th>
                <th style="width:13%">Savings / Current Account No.</th>
                <th style="width:11%">Obligation Request No.</th>
                <th style="width:9%">Allotment Class (UACS)</th>
                <th style="width:9%">Gross Amount</th>
                <th style="width:9%">Withholding Tax</th>
                <th style="width:9%">Net Amount</th>
                <th style="width:5%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lineItems as $item)
                @php
                    $lineWithholding = $grossAmount > 0 ? round($withholdingTaxAmount * ($item['amount'] / $grossAmount), 2) : 0;
                    $lineNet = max($item['amount'] - $lineWithholding, 0);
                @endphp
                <tr>
                    <td>{{ $item['sequence_no'] }}</td>
                    <td>{{ $item['reference_no'] }}</td>
                    <td>{{ $serviceProviderName }}</td>
                    <td>{{ $item['bank_name'] ?: '-' }}</td>
                    <td>{{ $item['account_number'] ?: '-' }}</td>
                    <td>{{ $orsNumber ?: '-' }}</td>
                    <td>{{ $item['allotment_class'] }}</td>
                    <td class="num">{{ number_format($item['amount'], 2) }}</td>
                    <td class="num">{{ number_format($lineWithholding, 2) }}</td>
                    <td class="num">{{ number_format($lineNet, 2) }}</td>
                    <td>{{ $item['remarks'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td style="width:75%;text-align:right">TOTAL</td>
            <td style="width:9%;text-align:right">{{ number_format($grossAmount, 2) }}</td>
            <td style="width:9%;text-align:right">{{ number_format($withholdingTaxAmount, 2) }}</td>
            <td style="width:9%;text-align:right">{{ number_format($netAmount, 2) }}</td>
            <td style="width:5%"></td>
        </tr>
    </table>

    <div class="signatures">
        <div class="sig-box">
            <p>I hereby warrant that the above List of Due and Demandable Accounts Payable was prepared in accordance with existing budgeting, accounting, and auditing rules and regulations.</p>
            <div class="sig-line">{{ $accountingApproverName ?: 'Pending accounting signatory' }}</div>
            <div class="muted">{{ $accountingApproverPosition }}</div>
            <div class="muted">Certified Correct - Accounting Division</div>
        </div>
        <div class="sig-box">
            <p>Budget certification for the obligated amount reflected in this advice to debit account.</p>
            <div class="sig-line">{{ $budgetApproverName ?: 'Pending budget signatory' }}</div>
            <div class="muted">{{ $budgetApproverPosition }}</div>
            <div class="muted">Budget Division</div>
        </div>
        <div class="sig-box">
            <p>Cash division review and release endorsement for the listed payment transfer.</p>
            <div class="sig-line">{{ $cashReviewerName ?: 'Pending cash signatory' }}</div>
            <div class="muted">{{ $cashReviewerPosition }}</div>
            <div class="muted">Cash Division</div>
        </div>
    </div>

    <div class="footnotes">
        <div>1. Agency shall arrange creditors on a first-in, first-out basis and ensure complete supporting documents are attached.</div>
        <div>2. Servicing bank should indicate non-payments under remarks when creditor account details do not match bank records.</div>
        <div>3. This is a system-generated printable LDDAP-ADA for GL workflow review.</div>
    </div>
</div>
</body>
</html>
