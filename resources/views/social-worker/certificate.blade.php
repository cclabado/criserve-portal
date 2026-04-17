<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Certificate of Eligibility</title>

<style>
body{
    font-family: Arial, sans-serif;
    margin:40px;
    color:#111;
}
.center{text-align:center;}
.title{
    font-size:28px;
    font-weight:bold;
    margin-top:20px;
}
.subtitle{
    font-size:16px;
    margin-top:4px;
}
.content{
    margin-top:40px;
    line-height:1.8;
    font-size:16px;
}
.signature{
    margin-top:80px;
    display:flex;
    justify-content:space-between;
}
.box{
    width:250px;
    text-align:center;
}
.line{
    border-top:1px solid #000;
    margin-bottom:5px;
}
.print-btn{
    margin-top:30px;
}
@media print{
    .print-btn{display:none;}
}
</style>
</head>

<body>

<div class="center">
    <div>Republic of the Philippines</div>
    <div><strong>Department of Social Welfare and Development</strong></div>
    <div class="title">CERTIFICATE OF ELIGIBILITY</div>
    <div class="subtitle">Assistance to Individuals in Crisis Situation</div>
</div>

<div class="content">

<p>To Whom It May Concern:</p>

<p>
This is to certify that <strong>
{{ $application->client->first_name }}
{{ $application->client->last_name }}
</strong>,
with Reference No. <strong>{{ $application->reference_no }}</strong>,
has been evaluated and found eligible for assistance under the
<strong>{{ $application->assistanceType->name ?? 'AICS Program' }}</strong>.
</p>

<p>
The approved amount for release is:
<strong>₱{{ number_format($application->final_amount, 2) }}</strong>.
</p>

<p>
Issued this <strong>{{ now()->format('F d, Y') }}</strong>
for whatever legal purpose it may serve.
</p>

</div>

<div class="signature">

    <div class="box">
        <div class="line"></div>
        Social Worker
    </div>

    <div class="box">
        <div class="line"></div>
        Approving Officer
    </div>

</div>

<div class="print-btn center">
    <button onclick="window.print()"
            style="padding:10px 20px;background:#234E70;color:white;border:none;border-radius:6px;">
        Print Certificate
    </button>
</div>

</body>
</html>