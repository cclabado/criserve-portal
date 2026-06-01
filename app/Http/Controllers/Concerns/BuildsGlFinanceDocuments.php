<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Application;

trait BuildsGlFinanceDocuments
{
    protected function renderGlOrsView(Application $application)
    {
        abort_unless($application->gl_ors_number, 404);

        return view('gl-payment-processor.ors', $this->glFinanceDocumentViewData($application));
    }

    protected function renderGlDvView(Application $application)
    {
        abort_unless($application->gl_dv_number, 404);

        return view('gl-payment-processor.dv', $this->glFinanceDocumentViewData($application));
    }

    protected function renderGlLddapAdaView(Application $application)
    {
        abort_unless($application->gl_lddap_ada_number, 404);

        return view('gl-payment-processor.lddap-ada', $this->glFinanceDocumentViewData($application));
    }

    protected function glFinanceDocumentViewData(Application $application): array
    {
        $application->loadMissing([
            'client',
            'assistanceType',
            'assistanceSubtype',
            'assistanceDetail',
            'serviceProvider',
            'approvingOfficer.position',
            'glBudgetApprover.position',
            'glAccountingApprover.position',
            'glCashReviewer.position',
            'glCashApprover.position',
            'documents',
        ]);

        $latestStatement = $application->documents
            ->where('document_type', 'Updated Statement of Account')
            ->sortByDesc('created_at')
            ->first();

        $clientName = $this->glDocumentPersonFullName($application->client);
        $payeeAddress = $application->serviceProvider?->address ?: 'Address not provided';
        $particulars = sprintf(
            'Payment for GL#%s of %s chargeable against: %s',
            $application->reference_no,
            $clientName !== '' ? $clientName : 'N/A',
            $application->gl_finance_fund_source ?: 'Fund source not set'
        );
        $amount = (float) ($application->gl_actual_utilized_amount ?? $application->final_amount ?? $application->recommended_amount ?? $application->amount_needed ?? 0);
        $withholdingTaxAmount = (float) ($application->gl_withholding_tax_amount ?? 0);
        $netAmount = max($amount - $withholdingTaxAmount, 0);
        $accountNumber = $latestStatement?->account_number_snapshot ?: null;
        $servicingBankName = $latestStatement?->bank_name_snapshot ?: 'Bank not provided';
        $servicingBankBranch = $application->gl_servicing_bank_branch ?: 'Branch not provided';
        $servicingBankDisplay = trim(implode(' - ', array_filter([$servicingBankName, $servicingBankBranch])));
        $allotmentClass = '5021499000';
        $paymentNature = trim(implode(' / ', array_filter([
            $application->assistanceType?->name,
            $application->assistanceSubtype?->name,
            $application->assistanceDetail?->name,
        ])));

        return [
            'application' => $application,
            'latestStatement' => $latestStatement,
            'clientName' => $clientName,
            'payeeAddress' => $payeeAddress,
            'particulars' => $particulars,
            'amount' => $amount,
            'uacsCode' => '50214990-00',
            'allotmentClass' => $allotmentClass,
            'entityName' => 'DEPARTMENT OF SOCIAL WELFARE AND DEVELOPMENT',
            'officeName' => 'Crisis Intervention Program',
            'requestingOfficerName' => $this->glDocumentPersonFullName($application->approvingOfficer) ?: ($application->approvingOfficer?->name ?? ''),
            'requestingOfficerPosition' => $application->approvingOfficer?->position?->name ?: 'Director IV, PMB',
            'budgetApproverName' => $this->glDocumentPersonFullName($application->glBudgetApprover) ?: ($application->glBudgetApprover?->name ?? ''),
            'budgetApproverPosition' => $application->glBudgetApprover?->position?->name ?: 'Chief, Budget Division',
            'accountingApproverName' => $this->glDocumentPersonFullName($application->glAccountingApprover) ?: ($application->glAccountingApprover?->name ?? ''),
            'accountingApproverPosition' => $application->glAccountingApprover?->position?->name ?: 'Chief, Accounting Division',
            'cashReviewerName' => $this->glDocumentPersonFullName($application->glCashReviewer) ?: ($application->glCashReviewer?->name ?? ''),
            'cashReviewerPosition' => $application->glCashReviewer?->position?->name ?: 'Cash Officer',
            'cashApproverName' => $this->glDocumentPersonFullName($application->glCashApprover) ?: ($application->glCashApprover?->name ?? ''),
            'cashApproverPosition' => $application->glCashApprover?->position?->name ?: 'Cash Approver',
            'bankAccountSummary' => $latestStatement?->bankAccountSummary(),
            'creditorAccountNumber' => $accountNumber,
            'creditorAccountName' => $latestStatement?->account_name_snapshot ?: ($application->serviceProvider?->name ?? '-'),
            'servicingBankName' => $servicingBankName,
            'servicingBankBranch' => $servicingBankBranch,
            'servicingBankDisplay' => $servicingBankDisplay,
            'withholdingTaxAmount' => $withholdingTaxAmount,
            'netAmount' => $netAmount,
            'totalAmountInWords' => $this->glCurrencyToWords($netAmount),
            'grossAmountInWords' => $this->glCurrencyToWords($amount),
            'paymentNature' => $paymentNature !== '' ? $paymentNature : 'Guarantee Letter payment',
        ];
    }

    protected function glDocumentPersonFullName($person): string
    {
        return trim(implode(' ', array_filter([
            $person?->first_name,
            $person?->middle_name,
            $person?->last_name,
            $person?->extension_name,
        ])));
    }

    protected function glCurrencyToWords(float $amount): string
    {
        $whole = (int) floor($amount);
        $fraction = (int) round(($amount - $whole) * 100);

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::SPELLOUT);
            $wholeWords = ucfirst((string) $formatter->format($whole));
            $fractionWords = $fraction > 0
                ? ucfirst((string) $formatter->format($fraction)).' Centavos'
                : 'Zero Centavos';

            return sprintf('%s Philippine Pesos And %s Only', $wholeWords, $fractionWords);
        }

        return sprintf('PHP %s', number_format($amount, 2));
    }
}
