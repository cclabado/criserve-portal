<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DV {{ $application->gl_dv_number }}</title>
<style>
    *{box-sizing:border-box}
    body{margin:0;background:#f3f4f6;color:#111;font-family:"Times New Roman",serif}
    .page{width:210mm;min-height:297mm;margin:12px auto;background:#fff;padding:12mm 14mm;box-shadow:0 8px 24px rgba(0,0,0,.08)}
    .print-btn{text-align:center;margin:20px 0}
    .print-btn button{padding:10px 18px;border:0;border-radius:8px;background:#234E70;color:#fff;font:600 14px Arial,sans-serif;cursor:pointer}
    .header{text-align:center}
    .header .small{font-size:12px}
    .header .title{font-size:20px;font-weight:700;letter-spacing:.06em}
    .meta{margin-top:10px;font-size:13px}
    .meta td,.particulars td,.particulars th,.entry td,.entry th{border:1px solid #111;padding:6px;vertical-align:top}
    .meta,.particulars,.entry{width:100%;border-collapse:collapse}
    .label{font-weight:700;width:18%}
    .particulars{margin-top:12px}
    .particulars th,.entry th{font-size:12px;text-transform:uppercase}
    .certs{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px}
    .box{border:1px solid #111;padding:10px;min-height:142px;font-size:13px}
    .box h3{margin:0 0 8px;font-size:14px}
    .sig{margin-top:28px;border-top:1px solid #111;padding-top:4px;font-weight:700}
    .sub{font-size:12px}
    .entry{margin-top:14px}
    .receipt{margin-top:14px;border:1px solid #111;padding:10px;font-size:13px}
    .receipt-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 20px}
    @media print{
        body{background:#fff}
        .page{width:auto;min-height:auto;margin:0;box-shadow:none}
        .print-btn{display:none}
        @page{size:A4 portrait;margin:10mm}
    }
</style>
</head>
<body>
<div class="print-btn"><button onclick="window.print()">Print DV</button></div>
<div class="page">
    <div class="header">
        <div class="small">Republic of the Philippines</div>
        <div class="small">{{ $entityName }}</div>
        <div class="title">DISBURSEMENT VOUCHER</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Fund Cluster</td>
            <td>{{ $application->gl_fund_cluster ?: '-' }}</td>
            <td class="label">Date</td>
            <td>{{ optional($application->gl_dv_date)->format('Y-m-d') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">DV No.</td>
            <td>{{ $application->gl_dv_number ?: '-' }}</td>
            <td class="label">Mode of Payment</td>
            <td>{{ $application->gl_mode_of_payment ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Payee</td>
            <td>{{ $application->serviceProvider?->name ?? '-' }}</td>
            <td class="label">TIN / Employee No.</td>
            <td>{{ $application->gl_payee_tin ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">ORS/BURS No.</td>
            <td>{{ $application->gl_ors_number ?: '-' }}</td>
            <td class="label">Address</td>
            <td>{{ $payeeAddress }}</td>
        </tr>
    </table>

    <table class="particulars">
        <thead>
            <tr>
                <th>Particulars</th>
                <th style="width:18%">Responsibility Center</th>
                <th style="width:18%">MFO/PAP</th>
                <th style="width:18%">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $particulars }}</td>
                <td>{{ $application->gl_responsibility_center ?: '-' }}</td>
                <td>{{ $application->gl_mfo_pap ?: '-' }}</td>
                <td style="text-align:right">{{ number_format($amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="certs">
        <div class="box">
            <h3>A. Certified</h3>
            <p>Expenses / cash advance necessary, lawful and incurred under my direct supervision.</p>
            <div class="sig">{{ $requestingOfficerName ?: 'Pending signatory' }}</div>
            <div class="sub">{{ $requestingOfficerPosition }}</div>
        </div>
        <div class="box">
            <h3>D. Approved for Payment</h3>
            <p>Final approval for payment.</p>
            <div class="sig">{{ $requestingOfficerName ?: 'Pending signatory' }}</div>
            <div class="sub">{{ $requestingOfficerPosition }}</div>
        </div>
    </div>

    <table class="entry">
        <thead>
            <tr>
                <th colspan="4">B. Accounting Entry</th>
            </tr>
            <tr>
                <th>Account Title</th>
                <th style="width:18%">UACS Code</th>
                <th style="width:18%">Debit</th>
                <th style="width:18%">Credit</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Subsidies - Others</td>
                <td>50214990</td>
                <td style="text-align:right">{{ number_format($amount, 2) }}</td>
                <td></td>
            </tr>
            <tr>
                <td>Cash - Modified Disbursement System (MDS)</td>
                <td>10104040</td>
                <td></td>
                <td style="text-align:right">{{ number_format($amount, 2) }}</td>
            </tr>
            <tr>
                <td>Due to BIR</td>
                <td>20201010</td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="certs" style="margin-top:12px">
        <div class="box">
            <h3>C. Certified</h3>
            <p>Accounting review and certification.</p>
            <div class="sig">{{ $accountingApproverName ?: 'Pending accounting approver' }}</div>
            <div class="sub">{{ $accountingApproverPosition }}</div>
        </div>
        <div class="box">
            <h3>E. Receipt of Payment</h3>
            <div class="receipt-grid">
                <div><strong>JEV No.:</strong> ____________________</div>
                <div><strong>Check / ADA No.:</strong> ____________________</div>
                <div><strong>Date:</strong> ____________________</div>
                <div><strong>Bank / Account:</strong> {{ $bankAccountSummary ?: 'Pending bank account' }}</div>
                <div><strong>Printed Name:</strong> ____________________</div>
                <div><strong>Signature:</strong> ____________________</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
