<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ORS {{ $application->gl_ors_number }}</title>
<style>
    *{box-sizing:border-box}
    body{margin:0;background:#f3f4f6;color:#111;font-family:"Times New Roman",serif}
    .page{width:210mm;min-height:297mm;margin:12px auto;background:#fff;padding:12mm 14mm;box-shadow:0 8px 24px rgba(0,0,0,.08)}
    .print-btn{text-align:center;margin:20px 0}
    .print-btn button{padding:10px 18px;border:0;border-radius:8px;background:#234E70;color:#fff;font:600 14px Arial,sans-serif;cursor:pointer}
    .title{text-align:center;font-weight:700;font-size:18px;letter-spacing:.04em}
    .topline{display:flex;justify-content:space-between;gap:18px;font-size:14px;margin-top:6px}
    table{width:100%;border-collapse:collapse}
    .meta{margin-top:8px;font-size:13px}
    .meta td{padding:4px 6px;border:1px solid #111;vertical-align:top}
    .meta .label{width:18%;font-weight:700}
    .details{margin-top:12px;font-size:13px}
    .details th,.details td{border:1px solid #111;padding:6px;vertical-align:top}
    .details th{font-size:12px;text-transform:uppercase}
    .certs{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px}
    .box{border:1px solid #111;padding:10px;min-height:148px;font-size:13px}
    .box h3{margin:0 0 8px;font-size:14px}
    .sig{margin-top:28px;border-top:1px solid #111;padding-top:4px;font-weight:700}
    .sub{font-size:12px}
    .status{margin-top:14px;font-size:13px}
    .status th,.status td{border:1px solid #111;padding:6px;vertical-align:top}
    .foot{margin-top:10px;font-size:11px;text-align:right}
    @media print{
        body{background:#fff}
        .page{width:auto;min-height:auto;margin:0;box-shadow:none}
        .print-btn{display:none}
        @page{size:A4 portrait;margin:10mm}
    }
</style>
</head>
<body>
<div class="print-btn"><button onclick="window.print()">Print ORS</button></div>
<div class="page">
    <div class="topline">
        <div><strong>No.:</strong> {{ $application->gl_ors_number }}</div>
        <div><strong>Date:</strong> {{ optional($application->gl_ors_date)->format('Y-m-d') ?? '-' }}</div>
    </div>
    <div class="title">OBLIGATION REQUEST AND STATUS</div>

    <table class="meta">
        <tr>
            <td colspan="2"><strong>{{ $entityName }}</strong></td>
            <td class="label">Fund</td>
            <td>{{ $application->gl_fund_cluster ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Payee</td>
            <td>{{ $application->serviceProvider?->name ?? '-' }}</td>
            <td class="label">Office</td>
            <td>{{ $officeName }}</td>
        </tr>
        <tr>
            <td class="label">Address</td>
            <td colspan="3">{{ $payeeAddress }}</td>
        </tr>
    </table>

    <table class="details">
        <thead>
            <tr>
                <th style="width:18%">Responsibility Center</th>
                <th>Particulars</th>
                <th style="width:16%">MFO/PAP</th>
                <th style="width:16%">UACS Code / Expenditure</th>
                <th style="width:16%">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $application->gl_responsibility_center ?: '-' }}</td>
                <td>{{ $particulars }}</td>
                <td>{{ $application->gl_mfo_pap ?: '-' }}</td>
                <td>{{ $uacsCode }}</td>
                <td style="text-align:right">{{ number_format($amount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right"><strong>Total</strong></td>
                <td style="text-align:right"><strong>{{ number_format($amount, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="certs">
        <div class="box">
            <h3>A. Certified</h3>
            <p>Charges to appropriation/allotment are necessary, lawful and under my direct supervision; and supporting documents are valid, proper and legal.</p>
            <div class="sig">{{ $requestingOfficerName ?: 'Pending signatory' }}</div>
            <div class="sub">{{ $requestingOfficerPosition }}</div>
            <div class="sub">Head, Requesting Office / Authorized Representative</div>
        </div>
        <div class="box">
            <h3>B. Certified</h3>
            <p>Allotment available and obligated for the purpose / adjustment necessary as indicated above.</p>
            <div class="sig">{{ $budgetApproverName ?: 'Pending budget approver' }}</div>
            <div class="sub">{{ $budgetApproverPosition }}</div>
            <div class="sub">Head, Budget Unit / Authorized Representative</div>
        </div>
    </div>

    <table class="status">
        <thead>
            <tr>
                <th colspan="6">C. Status of Obligation</th>
            </tr>
            <tr>
                <th style="width:12%">Date</th>
                <th>Particulars</th>
                <th style="width:18%">ORS/JEV/RCI/RADAI No.</th>
                <th style="width:14%">Obligation</th>
                <th style="width:14%">Payment</th>
                <th style="width:14%">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ optional($application->gl_ors_date)->format('Y-m-d') ?? '-' }}</td>
                <td>Obligation</td>
                <td>{{ $application->gl_ors_number }}</td>
                <td style="text-align:right">{{ number_format($amount, 2) }}</td>
                <td style="text-align:right">0.00</td>
                <td style="text-align:right">{{ number_format($amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="foot">Generated {{ now()->format('F d, Y h:i A') }}</div>
</div>
</body>
</html>
