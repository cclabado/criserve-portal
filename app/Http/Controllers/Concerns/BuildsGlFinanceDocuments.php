<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Application;
use App\Models\GlFinanceBatch;

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

    protected function renderGlBatchOrsView(GlFinanceBatch $batch)
    {
        abort_unless($batch->ors_number, 404);

        return view('gl-payment-processor.ors', $this->glFinanceBatchDocumentViewData($batch));
    }

    protected function renderGlBatchDvView(GlFinanceBatch $batch)
    {
        abort_unless($batch->dv_number, 404);

        return view('gl-payment-processor.dv', $this->glFinanceBatchDocumentViewData($batch));
    }

    protected function renderGlBatchLddapAdaView(GlFinanceBatch $batch)
    {
        abort_unless($batch->lddap_ada_number, 404);

        return view('gl-payment-processor.lddap-ada', $this->glFinanceBatchDocumentViewData($batch));
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
        $lineItems = [[
            'sequence_no' => 1,
            'reference_no' => $application->reference_no,
            'client_name' => $clientName !== '' ? $clientName : 'N/A',
            'particulars' => $particulars,
            'amount' => $amount,
            'bank_name' => $servicingBankName,
            'account_number' => $accountNumber,
            'payment_nature' => $paymentNature !== '' ? $paymentNature : 'Guarantee Letter payment',
            'allotment_class' => $allotmentClass,
            'remarks' => $paymentNature !== '' ? $paymentNature : 'Guarantee Letter payment',
        ]];

        return [
            'application' => $application,
            'batch' => null,
            'latestStatement' => $latestStatement,
            'clientName' => $clientName,
            'payeeAddress' => $payeeAddress,
            'particulars' => $particulars,
            'amount' => $amount,
            'grossAmount' => $amount,
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
            'serviceProviderName' => $application->serviceProvider?->name ?? '-',
            'fundCluster' => $application->gl_fund_cluster ?: '-',
            'responsibilityCenter' => $application->gl_responsibility_center ?: '-',
            'mfoPap' => $application->gl_mfo_pap ?: '-',
            'modeOfPayment' => $application->gl_mode_of_payment ?: '-',
            'payeeTin' => $application->gl_payee_tin ?: '-',
            'documentReferenceNumber' => $application->reference_no,
            'documentBatchNo' => null,
            'includedCount' => 1,
            'lineItems' => $lineItems,
            'documentScopeLabel' => 'record',
            'orsNumber' => $application->gl_ors_number,
            'orsDate' => $application->gl_ors_date,
            'dvNumber' => $application->gl_dv_number,
            'dvDate' => $application->gl_dv_date,
            'lddapAdaNumber' => $application->gl_lddap_ada_number,
            'lddapAdaDate' => $application->gl_lddap_ada_date,
            'ncaNumber' => $application->gl_nca_number ?: '-',
            'ncaDate' => $application->gl_nca_date,
        ];
    }

    protected function glFinanceBatchDocumentViewData(GlFinanceBatch $batch): array
    {
        $batch->loadMissing([
            'serviceProvider',
            'bankAccount.bank',
            'programApprover.position',
            'applications.client',
            'applications.assistanceType',
            'applications.assistanceSubtype',
            'applications.assistanceDetail',
            'applications.serviceProvider',
            'applications.approvingOfficer.position',
            'applications.glBudgetApprover.position',
            'applications.glAccountingApprover.position',
            'applications.glCashReviewer.position',
            'applications.glCashApprover.position',
            'applications.documents',
        ]);

        $applications = $batch->applications->sortBy(fn ($application) => (int) $application->pivot->sequence_no)->values();
        abort_if($applications->isEmpty(), 404);

        $primaryApplication = $applications->first();
        $bankAccount = $batch->bankAccount;
        $payeeAddress = $batch->serviceProvider?->address ?: 'Address not provided';
        $serviceProviderName = $batch->serviceProvider?->name ?? ($primaryApplication->serviceProvider?->name ?? '-');
        $grossAmount = (float) $applications->sum(fn ($application) => (float) $application->pivot->utilized_amount);
        $withholdingTaxAmount = (float) ($batch->withholding_tax_amount ?? 0);
        $netAmount = max($grossAmount - $withholdingTaxAmount, 0);
        $servicingBankName = $bankAccount?->resolvedBankName() ?: 'Bank not provided';
        $servicingBankBranch = $batch->servicing_bank_branch ?: ($bankAccount?->branch_name ?: 'Branch not provided');
        $servicingBankDisplay = trim(implode(' - ', array_filter([$servicingBankName, $servicingBankBranch])));
        $creditorAccountNumber = $bankAccount?->account_number;
        $creditorAccountName = $bankAccount?->account_name ?: $serviceProviderName;
        $allotmentClass = '5021499000';
        $clientDisplayName = $applications->count() === 1
            ? $this->glDocumentPersonFullName($primaryApplication->client)
            : 'Multiple Clients';
        $particulars = sprintf(
            'Payment for GL Batch %s covering %d transaction(s) chargeable against: %s',
            $batch->batch_no,
            $applications->count(),
            $batch->finance_fund_source_name ?: 'Fund source not set'
        );
        $paymentNature = trim(implode(' / ', array_filter([
            $primaryApplication->assistanceType?->name,
            $primaryApplication->assistanceSubtype?->name,
            $primaryApplication->assistanceDetail?->name,
        ])));

        $lineItems = $applications->map(function ($application) use ($servicingBankName, $creditorAccountNumber, $allotmentClass) {
            $clientName = $this->glDocumentPersonFullName($application->client);
            $detailParts = array_filter([
                $application->assistanceType?->name,
                $application->assistanceSubtype?->name,
                $application->assistanceDetail?->name,
            ]);
            $paymentNature = implode(' / ', $detailParts);

            return [
                'sequence_no' => (int) $application->pivot->sequence_no,
                'reference_no' => $application->reference_no,
                'client_name' => $clientName !== '' ? $clientName : 'N/A',
                'particulars' => sprintf(
                    'Payment for GL#%s of %s',
                    $application->reference_no,
                    $clientName !== '' ? $clientName : 'N/A'
                ),
                'amount' => (float) $application->pivot->utilized_amount,
                'bank_name' => $servicingBankName,
                'account_number' => $creditorAccountNumber,
                'payment_nature' => $paymentNature !== '' ? $paymentNature : 'Guarantee Letter payment',
                'allotment_class' => $allotmentClass,
                'remarks' => $paymentNature !== '' ? $paymentNature : 'Guarantee Letter payment',
            ];
        })->values();

        return [
            'application' => $primaryApplication,
            'batch' => $batch,
            'applications' => $applications,
            'latestStatement' => null,
            'clientName' => $clientDisplayName,
            'payeeAddress' => $payeeAddress,
            'particulars' => $particulars,
            'amount' => $grossAmount,
            'grossAmount' => $grossAmount,
            'uacsCode' => '50214990-00',
            'allotmentClass' => $allotmentClass,
            'entityName' => 'DEPARTMENT OF SOCIAL WELFARE AND DEVELOPMENT',
            'officeName' => 'Crisis Intervention Program',
            'requestingOfficerName' => $this->glDocumentPersonFullName($batch->programApprover) ?: ($this->glDocumentPersonFullName($primaryApplication->approvingOfficer) ?: ($primaryApplication->approvingOfficer?->name ?? '')),
            'requestingOfficerPosition' => $batch->programApprover?->position?->name ?: ($primaryApplication->approvingOfficer?->position?->name ?: 'Director IV, PMB'),
            'budgetApproverName' => $this->glDocumentPersonFullName($primaryApplication->glBudgetApprover) ?: ($primaryApplication->glBudgetApprover?->name ?? ''),
            'budgetApproverPosition' => $primaryApplication->glBudgetApprover?->position?->name ?: 'Chief, Budget Division',
            'accountingApproverName' => $this->glDocumentPersonFullName($primaryApplication->glAccountingApprover) ?: ($primaryApplication->glAccountingApprover?->name ?? ''),
            'accountingApproverPosition' => $primaryApplication->glAccountingApprover?->position?->name ?: 'Chief, Accounting Division',
            'cashReviewerName' => $this->glDocumentPersonFullName($primaryApplication->glCashReviewer) ?: ($primaryApplication->glCashReviewer?->name ?? ''),
            'cashReviewerPosition' => $primaryApplication->glCashReviewer?->position?->name ?: 'Cash Officer',
            'cashApproverName' => $this->glDocumentPersonFullName($primaryApplication->glCashApprover) ?: ($primaryApplication->glCashApprover?->name ?? ''),
            'cashApproverPosition' => $primaryApplication->glCashApprover?->position?->name ?: 'Cash Approver',
            'bankAccountSummary' => $bankAccount?->displayLabel(),
            'creditorAccountNumber' => $creditorAccountNumber,
            'creditorAccountName' => $creditorAccountName,
            'servicingBankName' => $servicingBankName,
            'servicingBankBranch' => $servicingBankBranch,
            'servicingBankDisplay' => $servicingBankDisplay,
            'withholdingTaxAmount' => $withholdingTaxAmount,
            'netAmount' => $netAmount,
            'totalAmountInWords' => $this->glCurrencyToWords($netAmount),
            'grossAmountInWords' => $this->glCurrencyToWords($grossAmount),
            'paymentNature' => $paymentNature !== '' ? $paymentNature : 'Guarantee Letter payment',
            'serviceProviderName' => $serviceProviderName,
            'fundCluster' => $batch->fund_cluster ?: '-',
            'responsibilityCenter' => $batch->responsibility_center ?: '-',
            'mfoPap' => $batch->mfo_pap ?: '-',
            'modeOfPayment' => $batch->mode_of_payment ?: '-',
            'payeeTin' => $batch->payee_tin ?: '-',
            'documentReferenceNumber' => $primaryApplication->reference_no,
            'documentBatchNo' => $batch->batch_no,
            'includedCount' => $applications->count(),
            'lineItems' => $lineItems,
            'documentScopeLabel' => 'batch',
            'orsNumber' => $batch->ors_number,
            'orsDate' => $batch->ors_date,
            'dvNumber' => $batch->dv_number,
            'dvDate' => $batch->dv_date,
            'lddapAdaNumber' => $batch->lddap_ada_number,
            'lddapAdaDate' => $batch->lddap_ada_date,
            'ncaNumber' => $batch->nca_number ?: '-',
            'ncaDate' => $batch->nca_date,
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
