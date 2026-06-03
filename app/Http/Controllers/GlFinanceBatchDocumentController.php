<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsGlFinanceDocuments;
use App\Models\GlFinanceBatch;
use Illuminate\Http\Request;

class GlFinanceBatchDocumentController extends Controller
{
    use BuildsGlFinanceDocuments;

    public function showOrs(Request $request, GlFinanceBatch $batch)
    {
        $this->authorizeBatchDocumentAccess($request, $batch);

        return $this->renderGlBatchOrsView($batch);
    }

    public function showDv(Request $request, GlFinanceBatch $batch)
    {
        $this->authorizeBatchDocumentAccess($request, $batch);

        return $this->renderGlBatchDvView($batch);
    }

    public function showLddapAda(Request $request, GlFinanceBatch $batch)
    {
        $this->authorizeBatchDocumentAccess($request, $batch);

        return $this->renderGlBatchLddapAdaView($batch);
    }

    protected function authorizeBatchDocumentAccess(Request $request, GlFinanceBatch $batch): void
    {
        $allowedRoles = [
            'admin',
            'gl_payment_processor',
            'approving_officer',
            'budget_officer',
            'budget_approver',
            'accounting_officer',
            'accounting_approver',
            'cash_officer',
            'cash_approver',
            'finance_director',
        ];

        abort_unless(in_array($request->user()?->role, $allowedRoles, true), 403);
        abort_unless($batch->applications()->exists(), 404);
    }
}
