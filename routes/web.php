<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BulkDeduplicationController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FinanceDirectorController;
use App\Http\Controllers\GlFinanceBatchDocumentController;
use App\Http\Controllers\GlPaymentProcessorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\SocialWorkerGoogleController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/support', [SupportTicketController::class, 'create'])
    ->name('support.create');
Route::post('/support', [SupportTicketController::class, 'store'])
    ->name('support.store');

/*
|--------------------------------------------------------------------------
| AUTH USER ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/stream', [DocumentController::class, 'stream'])->name('documents.stream');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/gl-finance-batches/{batch}/ors', [GlFinanceBatchDocumentController::class, 'showOrs'])
        ->name('gl-finance-batches.documents.ors');
    Route::get('/gl-finance-batches/{batch}/dv', [GlFinanceBatchDocumentController::class, 'showDv'])
        ->name('gl-finance-batches.documents.dv');
    Route::get('/gl-finance-batches/{batch}/lddap-ada', [GlFinanceBatchDocumentController::class, 'showLddapAda'])
        ->name('gl-finance-batches.documents.lddap-ada');
});

/*
|--------------------------------------------------------------------------
| CLIENT ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:client'])->group(function () {

    Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])
        ->name('client.dashboard');
    Route::get('/client/applications', [ClientDashboardController::class, 'applications'])
        ->name('client.applications');
    Route::get('/client/family', [ClientDashboardController::class, 'family'])
        ->name('client.family');
    Route::post('/client/family', [ClientDashboardController::class, 'updateFamily'])
        ->name('client.family.update');

    Route::get('/client/application', [ApplicationController::class, 'create']);
    Route::post('/client/beneficiary-profile/lookup', [ApplicationController::class, 'lookupBeneficiaryProfile'])
        ->name('client.beneficiary-profile.lookup');

    Route::post('/client/application', [ApplicationController::class, 'store']);

    Route::get('/client/application/{id}', [ClientDashboardController::class, 'show'])
        ->name('client.application.show');
    Route::post('/client/application/{id}/compliance-documents', [ClientDashboardController::class, 'uploadComplianceDocuments'])
        ->name('client.application.compliance-documents.upload');
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])
        ->name('admin.dashboard');
    Route::get('/admin/reports', [AdminController::class, 'reports'])
        ->name('admin.reports');
    Route::get('/admin/deduplication', [BulkDeduplicationController::class, 'index'])
        ->name('admin.deduplication.index');
    Route::post('/admin/deduplication', [BulkDeduplicationController::class, 'store'])
        ->name('admin.deduplication.store');
    Route::get('/admin/deduplication/{run}/status', [BulkDeduplicationController::class, 'status'])
        ->name('admin.deduplication.status');
    Route::get('/admin/deduplication/{run}/{type}', [BulkDeduplicationController::class, 'download'])
        ->name('admin.deduplication.download');
    Route::get('/admin/deduplication/{run}', [BulkDeduplicationController::class, 'show'])
        ->name('admin.deduplication.show');
    Route::get('/admin/payouts', [PayoutController::class, 'index'])
        ->name('admin.payouts.index');
    Route::post('/admin/payouts', [PayoutController::class, 'store'])
        ->name('admin.payouts.store');
    Route::patch('/admin/payouts/{batch}/activation', [PayoutController::class, 'updateActivation'])
        ->name('admin.payouts.activation.update');
    Route::get('/admin/payouts/{batch}/report', [PayoutController::class, 'exportReport'])
        ->name('admin.payouts.report');
    Route::get('/admin/payouts/{batch}', [PayoutController::class, 'show'])
        ->name('admin.payouts.show');
    Route::post('/admin/payouts/{batch}/entries/{entry}/claim', [PayoutController::class, 'claimEntry'])
        ->name('admin.payouts.entries.claim');
    Route::post('/admin/payouts/{batch}/entries/{entry}/release', [PayoutController::class, 'releaseEntry'])
        ->name('admin.payouts.entries.release');
    Route::patch('/admin/payouts/{batch}/entries/{entry}', [PayoutController::class, 'updateEntry'])
        ->name('admin.payouts.entries.update');
    Route::get('/admin/payouts/{batch}/entries/{entry}/proof-photo', [PayoutController::class, 'proofPhoto'])
        ->name('admin.payouts.entries.proof');
    Route::get('/admin/libraries', [AdminController::class, 'libraries'])
        ->name('admin.libraries');
    Route::get('/admin/libraries/{library}', [AdminController::class, 'showLibrary'])
        ->name('admin.libraries.show');
    Route::get('/admin/frequency-rules', [AdminController::class, 'frequencyRules'])
        ->name('admin.frequency-rules');
    Route::get('/admin/users', [AdminController::class, 'users'])
        ->name('admin.users');
    Route::get('/admin/audit-logs', [AdminController::class, 'auditLogs'])
        ->name('admin.audit-logs');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])
        ->name('admin.users.store');
    Route::get('/admin/support-tickets', [AdminController::class, 'supportTickets'])
        ->name('admin.support-tickets');
    Route::patch('/admin/support-tickets/{supportTicket}', [AdminController::class, 'updateSupportTicket'])
        ->name('admin.support-tickets.update');
    Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])
        ->name('admin.users.update');
    Route::patch('/admin/users/{user}/role', [AdminController::class, 'updateUserRole'])
        ->name('admin.users.role.update');
    Route::post('/admin/libraries/assistance-types', [AdminController::class, 'storeAssistanceType'])
        ->name('admin.libraries.assistance-types.store');
    Route::post('/admin/libraries/assistance-subtypes', [AdminController::class, 'storeAssistanceSubtype'])
        ->name('admin.libraries.assistance-subtypes.store');
    Route::post('/admin/libraries/assistance-details', [AdminController::class, 'storeAssistanceDetail'])
        ->name('admin.libraries.assistance-details.store');
    Route::post('/admin/libraries/document-requirements', [AdminController::class, 'storeDocumentRequirement'])
        ->name('admin.libraries.document-requirements.store');
    Route::post('/admin/libraries/modes-of-assistance', [AdminController::class, 'storeModeOfAssistance'])
        ->name('admin.libraries.modes-of-assistance.store');
    Route::post('/admin/libraries/service-points', [AdminController::class, 'storeServicePoint'])
        ->name('admin.libraries.service-points.store');
    Route::post('/admin/libraries/finance-fund-sources', [AdminController::class, 'storeFinanceFundSource'])
        ->name('admin.libraries.finance-fund-sources.store');
    Route::post('/admin/libraries/banks', [AdminController::class, 'storeBank'])
        ->name('admin.libraries.banks.store');
    Route::post('/admin/libraries/service-providers', [AdminController::class, 'storeServiceProvider'])
        ->name('admin.libraries.service-providers.store');
    Route::post('/admin/libraries/positions', [AdminController::class, 'storePosition'])
        ->name('admin.libraries.positions.store');
    Route::post('/admin/libraries/relationships', [AdminController::class, 'storeRelationship'])
        ->name('admin.libraries.relationships.store');
    Route::post('/admin/libraries/client-types', [AdminController::class, 'storeClientType'])
        ->name('admin.libraries.client-types.store');
    Route::post('/admin/libraries/referral-institutions', [AdminController::class, 'storeReferralInstitution'])
        ->name('admin.libraries.referral-institutions.store');
    Route::patch('/admin/libraries/{library}/{item}', [AdminController::class, 'updateLibrary'])
        ->name('admin.libraries.update');
    Route::delete('/admin/libraries/{library}/{item}', [AdminController::class, 'archiveLibrary'])
        ->name('admin.libraries.archive');
    Route::patch('/admin/libraries/{library}/{item}/restore', [AdminController::class, 'restoreLibrary'])
        ->name('admin.libraries.restore');
    Route::post('/admin/frequency-rules', [AdminController::class, 'storeFrequencyRule'])
        ->name('admin.frequency-rules.store');
    Route::patch('/admin/frequency-rules/{frequencyRule}', [AdminController::class, 'updateFrequencyRule'])
        ->name('admin.frequency-rules.update');
    Route::delete('/admin/frequency-rules/{frequencyRule}', [AdminController::class, 'destroyFrequencyRule'])
        ->name('admin.frequency-rules.destroy');

});

Route::middleware(['auth', 'role:reporting_officer'])->prefix('reporting-officer')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])
        ->name('reporting.dashboard');
    Route::get('/reports', [AdminController::class, 'reports'])
        ->name('reporting.reports');
    Route::get('/deduplication', [BulkDeduplicationController::class, 'index'])
        ->name('reporting.deduplication.index');
    Route::post('/deduplication', [BulkDeduplicationController::class, 'store'])
        ->name('reporting.deduplication.store');
    Route::get('/deduplication/{run}/status', [BulkDeduplicationController::class, 'status'])
        ->name('reporting.deduplication.status');
    Route::get('/deduplication/{run}/{type}', [BulkDeduplicationController::class, 'download'])
        ->name('reporting.deduplication.download');
    Route::get('/deduplication/{run}', [BulkDeduplicationController::class, 'show'])
        ->name('reporting.deduplication.show');
    Route::get('/payouts', [PayoutController::class, 'index'])
        ->name('reporting.payouts.index');
    Route::post('/payouts', [PayoutController::class, 'store'])
        ->name('reporting.payouts.store');
    Route::get('/payouts/{batch}/report', [PayoutController::class, 'exportReport'])
        ->name('reporting.payouts.report');
    Route::get('/payouts/{batch}', [PayoutController::class, 'show'])
        ->name('reporting.payouts.show');
    Route::post('/payouts/{batch}/entries/{entry}/claim', [PayoutController::class, 'claimEntry'])
        ->name('reporting.payouts.entries.claim');
    Route::post('/payouts/{batch}/entries/{entry}/release', [PayoutController::class, 'releaseEntry'])
        ->name('reporting.payouts.entries.release');
    Route::patch('/payouts/{batch}/entries/{entry}', [PayoutController::class, 'updateEntry'])
        ->name('reporting.payouts.entries.update');
    Route::get('/payouts/{batch}/entries/{entry}/proof-photo', [PayoutController::class, 'proofPhoto'])
        ->name('reporting.payouts.entries.proof');
});

Route::middleware(['auth', 'role:referral_institution'])->prefix('referral-institution')->group(function () {
    Route::get('/dashboard', [ReferralController::class, 'institutionDashboard'])
        ->name('referral-institution.dashboard');
    Route::get('/application/create', [ReferralController::class, 'createInstitutionApplication'])
        ->name('referral-institution.applications.create');
    Route::post('/application', [ReferralController::class, 'storeInstitutionApplication'])
        ->name('referral-institution.applications.store');
    Route::patch('/referrals/{recommendation}', [ReferralController::class, 'updateReferral'])
        ->name('referral-institution.referrals.update');
});

Route::middleware(['auth', 'role:referral_officer'])->prefix('referral-officer')->group(function () {
    Route::get('/dashboard', [ReferralController::class, 'officerDashboard'])
        ->name('referral-officer.dashboard');
    Route::get('/payouts', [PayoutController::class, 'index'])
        ->name('referral-officer.payouts.index');
    Route::get('/payouts/{batch}', [PayoutController::class, 'show'])
        ->name('referral-officer.payouts.show');
    Route::post('/payouts/{batch}/entries/{entry}/claim', [PayoutController::class, 'claimEntry'])
        ->name('referral-officer.payouts.entries.claim');
    Route::post('/payouts/{batch}/entries/{entry}/release', [PayoutController::class, 'releaseEntry'])
        ->name('referral-officer.payouts.entries.release');
    Route::patch('/payouts/{batch}/entries/{entry}', [PayoutController::class, 'updateEntry'])
        ->name('referral-officer.payouts.entries.update');
    Route::get('/payouts/{batch}/entries/{entry}/proof-photo', [PayoutController::class, 'proofPhoto'])
        ->name('referral-officer.payouts.entries.proof');
    Route::patch('/referrals/{recommendation}', [ReferralController::class, 'updateReferral'])
        ->name('referral-officer.referrals.update');
    Route::patch('/institution-referrals/{institutionReferral}', [ReferralController::class, 'updateInstitutionReferral'])
        ->name('referral-officer.institution-referrals.update');
});

Route::middleware(['auth', 'role:technical_staff'])->prefix('technical-staff')->group(function () {
    Route::get('/payouts', [PayoutController::class, 'index'])
        ->name('technical-staff.payouts.index');
    Route::get('/payouts/{batch}', [PayoutController::class, 'show'])
        ->name('technical-staff.payouts.show');
    Route::post('/payouts/{batch}/entries/{entry}/claim', [PayoutController::class, 'claimEntry'])
        ->name('technical-staff.payouts.entries.claim');
    Route::post('/payouts/{batch}/entries/{entry}/release', [PayoutController::class, 'releaseEntry'])
        ->name('technical-staff.payouts.entries.release');
    Route::patch('/payouts/{batch}/entries/{entry}', [PayoutController::class, 'updateEntry'])
        ->name('technical-staff.payouts.entries.update');
    Route::get('/payouts/{batch}/entries/{entry}/proof-photo', [PayoutController::class, 'proofPhoto'])
        ->name('technical-staff.payouts.entries.proof');
});

Route::middleware(['auth', 'role:admin_staff'])->prefix('admin-staff')->group(function () {
    Route::get('/payouts', [PayoutController::class, 'index'])
        ->name('admin-staff.payouts.index');
    Route::get('/payouts/{batch}', [PayoutController::class, 'show'])
        ->name('admin-staff.payouts.show');
    Route::post('/payouts/{batch}/entries/{entry}/claim', [PayoutController::class, 'claimEntry'])
        ->name('admin-staff.payouts.entries.claim');
    Route::post('/payouts/{batch}/entries/{entry}/release', [PayoutController::class, 'releaseEntry'])
        ->name('admin-staff.payouts.entries.release');
    Route::patch('/payouts/{batch}/entries/{entry}', [PayoutController::class, 'updateEntry'])
        ->name('admin-staff.payouts.entries.update');
    Route::get('/payouts/{batch}/entries/{entry}/proof-photo', [PayoutController::class, 'proofPhoto'])
        ->name('admin-staff.payouts.entries.proof');
});

/*
|--------------------------------------------------------------------------
| SOCIAL WORKER ROUTES
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\SocialWorkerController;

Route::middleware(['auth', 'role:social_worker'])->group(function () {

    Route::get('/social-worker/dashboard', [SocialWorkerController::class, 'dashboard']);
    Route::get('/social-worker/payouts', [PayoutController::class, 'index'])
        ->name('socialworker.payouts.index');
    Route::get('/social-worker/payouts/{batch}', [PayoutController::class, 'show'])
        ->name('socialworker.payouts.show');
    Route::post('/social-worker/payouts/{batch}/entries/{entry}/claim', [PayoutController::class, 'claimEntry'])
        ->name('socialworker.payouts.entries.claim');
    Route::post('/social-worker/payouts/{batch}/entries/{entry}/release', [PayoutController::class, 'releaseEntry'])
        ->name('socialworker.payouts.entries.release');
    Route::patch('/social-worker/payouts/{batch}/entries/{entry}', [PayoutController::class, 'updateEntry'])
        ->name('socialworker.payouts.entries.update');
    Route::get('/social-worker/payouts/{batch}/entries/{entry}/proof-photo', [PayoutController::class, 'proofPhoto'])
        ->name('socialworker.payouts.entries.proof');
    Route::get('/social-worker/google/connect', [SocialWorkerGoogleController::class, 'redirect'])
        ->name('socialworker.google.connect');
    Route::get('/social-worker/google/callback', [SocialWorkerGoogleController::class, 'callback'])
        ->name('socialworker.google.callback');
    Route::delete('/social-worker/google/disconnect', [SocialWorkerGoogleController::class, 'disconnect'])
        ->name('socialworker.google.disconnect');
    Route::get('/social-worker/applications', [SocialWorkerController::class, 'applications'])
        ->name('socialworker.applications');
    Route::get('/social-worker/my-cases', [SocialWorkerController::class, 'myCases'])
        ->name('socialworker.my-cases');
    Route::get('/social-worker/schedule', [SocialWorkerController::class, 'schedule'])
        ->name('socialworker.schedule');
    Route::get('/social-worker/application/{id}', [SocialWorkerController::class, 'show']);
    Route::get('/social-worker/application/{id}/assess', [SocialWorkerController::class, 'assess']);
    Route::post('/social-worker/application/{id}/assess', [SocialWorkerController::class, 'updateAssessment'])
        ->name('socialworker.assess.update');
    Route::get('/social-worker/application/{id}/intake', [SocialWorkerController::class, 'intake'])
        ->name('socialworker.intake');
    Route::post('/social-worker/application/{id}/recommendation', [SocialWorkerController::class, 'generateRecommendation'])
        ->name('socialworker.recommendation.generate');
    Route::post('/social-worker/application/{id}/assistance-frequency', [SocialWorkerController::class, 'checkAdditionalAssistanceFrequency'])
        ->name('socialworker.assistance-frequency.check');
    Route::post('/social-worker/application/{id}/intake', [SocialWorkerController::class, 'saveIntake'])
        ->name('socialworker.intake.save');
    Route::get('/social-worker/application/{id}/show',
        [SocialWorkerController::class, 'show'])
        ->name('socialworker.show');
    Route::get('/social-worker/application/{id}/certificate',
        [SocialWorkerController::class, 'certificate'])
        ->name('socialworker.certificate');
    Route::get('/social-worker/application/{id}/general-intake-sheet',
        [SocialWorkerController::class, 'generalIntakeSheet'])
        ->name('socialworker.general-intake-sheet');
    Route::get('/social-worker/application/{id}/guarantee-letter',
        [SocialWorkerController::class, 'guaranteeLetter'])
        ->name('socialworker.guarantee-letter');
    Route::post('/social-worker/application/{id}/release',
        [SocialWorkerController::class, 'release'])
        ->name('socialworker.release');
});

Route::middleware(['auth', 'role:service_provider'])->group(function () {
    Route::get('/service-provider/dashboard', [ServiceProviderController::class, 'dashboard'])
        ->name('service-provider.dashboard');
    Route::get('/service-provider/bank-accounts', [ServiceProviderController::class, 'bankAccounts'])
        ->name('service-provider.bank-accounts');
    Route::post('/service-provider/bank-accounts', [ServiceProviderController::class, 'storeBankAccount'])
        ->name('service-provider.bank-accounts.store');
    Route::patch('/service-provider/bank-accounts/{bankAccount}/default', [ServiceProviderController::class, 'setDefaultBankAccount'])
        ->name('service-provider.bank-accounts.default');
    Route::get('/service-provider/guarantee-letters', [ServiceProviderController::class, 'letters'])
        ->name('service-provider.letters');
    Route::get('/service-provider/guarantee-letters/{application}', [ServiceProviderController::class, 'show'])
        ->name('service-provider.show');
    Route::get('/service-provider/guarantee-letters/{application}/print', [ServiceProviderController::class, 'guaranteeLetter'])
        ->name('service-provider.guarantee-letter');
    Route::post('/service-provider/guarantee-letters/{application}/statement', [ServiceProviderController::class, 'uploadUpdatedStatement'])
        ->name('service-provider.statement.upload');
    Route::post('/service-provider/guarantee-letters/{application}/supporting-documents', [ServiceProviderController::class, 'uploadSupportingDocument'])
        ->name('service-provider.supporting-documents.upload');
    Route::post('/service-provider/guarantee-letters/{application}/attachments', [ServiceProviderController::class, 'submitAttachments'])
        ->name('service-provider.attachments.submit');
});

Route::middleware(['auth', 'role:gl_payment_processor'])->prefix('gl-payment-processor')->group(function () {
    Route::get('/dashboard', [GlPaymentProcessorController::class, 'dashboard'])
        ->name('gl-payment-processor.dashboard');
    Route::get('/guarantee-letters', [GlPaymentProcessorController::class, 'queue'])
        ->name('gl-payment-processor.queue');
    Route::get('/finance-batches/ready', [GlPaymentProcessorController::class, 'readyForBatch'])
        ->name('gl-payment-processor.finance-batches.ready');
    Route::post('/finance-batches', [GlPaymentProcessorController::class, 'storeFinanceBatch'])
        ->name('gl-payment-processor.finance-batches.store');
    Route::get('/finance-batches/{batch}', [GlPaymentProcessorController::class, 'showFinanceBatch'])
        ->name('gl-payment-processor.finance-batches.show');
    Route::get('/payouts', [PayoutController::class, 'index'])
        ->name('gl-payment-processor.payouts.index');
    Route::get('/payouts/{batch}', [PayoutController::class, 'show'])
        ->name('gl-payment-processor.payouts.show');
    Route::post('/payouts/{batch}/entries/{entry}/claim', [PayoutController::class, 'claimEntry'])
        ->name('gl-payment-processor.payouts.entries.claim');
    Route::post('/payouts/{batch}/entries/{entry}/release', [PayoutController::class, 'releaseEntry'])
        ->name('gl-payment-processor.payouts.entries.release');
    Route::patch('/payouts/{batch}/entries/{entry}', [PayoutController::class, 'updateEntry'])
        ->name('gl-payment-processor.payouts.entries.update');
    Route::get('/payouts/{batch}/entries/{entry}/proof-photo', [PayoutController::class, 'proofPhoto'])
        ->name('gl-payment-processor.payouts.entries.proof');
    Route::get('/guarantee-letters/{application}', [GlPaymentProcessorController::class, 'show'])
        ->name('gl-payment-processor.show');
    Route::get('/guarantee-letters/{application}/print', [GlPaymentProcessorController::class, 'guaranteeLetter'])
        ->name('gl-payment-processor.guarantee-letter');
    Route::get('/guarantee-letters/{application}/ors', [GlPaymentProcessorController::class, 'ors'])
        ->name('gl-payment-processor.ors');
    Route::get('/guarantee-letters/{application}/dv', [GlPaymentProcessorController::class, 'dv'])
        ->name('gl-payment-processor.dv');
    Route::get('/guarantee-letters/{application}/lddap-ada', [GlPaymentProcessorController::class, 'lddapAda'])
        ->name('gl-payment-processor.lddap-ada');
    Route::patch('/guarantee-letters/{application}/soa-review', [GlPaymentProcessorController::class, 'updateSoaReview'])
        ->name('gl-payment-processor.soa-review.update');
    Route::patch('/guarantee-letters/{application}/budget-processing', [GlPaymentProcessorController::class, 'submitBudgetProcessing'])
        ->name('gl-payment-processor.budget-processing.submit');
});
/*
|--------------------------------------------------------------------------
| APPROVING OFFICER ROUTES
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\ApprovingOfficerController;

Route::middleware(['auth', 'role:approving_officer'])->group(function () {
    Route::prefix('approving-officer')->group(function () {

        Route::get('/dashboard', [ApprovingOfficerController::class, 'dashboard'])
            ->name('approving.dashboard');
        Route::get('/payouts', [PayoutController::class, 'index'])
            ->name('approving.payouts.index');
        Route::get('/payouts/{batch}', [PayoutController::class, 'show'])
            ->name('approving.payouts.show');
        Route::post('/payouts/{batch}/entries/{entry}/claim', [PayoutController::class, 'claimEntry'])
            ->name('approving.payouts.entries.claim');
        Route::post('/payouts/{batch}/entries/{entry}/release', [PayoutController::class, 'releaseEntry'])
            ->name('approving.payouts.entries.release');
        Route::patch('/payouts/{batch}/entries/{entry}', [PayoutController::class, 'updateEntry'])
            ->name('approving.payouts.entries.update');
        Route::get('/payouts/{batch}/entries/{entry}/proof-photo', [PayoutController::class, 'proofPhoto'])
            ->name('approving.payouts.entries.proof');

        Route::get('/applications', [ApprovingOfficerController::class, 'applications'])
            ->name('approving.applications');

        Route::get('/my-approvals', [ApprovingOfficerController::class, 'myApprovals'])
            ->name('approving.my-approvals');

        Route::get('/gl-payment-approvals', [ApprovingOfficerController::class, 'glPaymentApprovals'])
            ->name('approving.gl-payment-approvals');
        Route::get('/gl-payment-approvals/{id}', [ApprovingOfficerController::class, 'showGlPaymentApproval'])
            ->name('approving.gl-payment-approvals.show');
        Route::get('/gl-payment-approvals/{batchId}/records/{applicationId}', [ApprovingOfficerController::class, 'showGlPaymentApprovalBatchRecord'])
            ->name('approving.gl-payment-approvals.records.show');
        Route::get('/gl-payment-approvals/{id}/ors', [ApprovingOfficerController::class, 'showGlFinanceOrs'])
            ->name('approving.gl-payment-approvals.ors');
        Route::get('/gl-payment-approvals/{id}/dv', [ApprovingOfficerController::class, 'showGlFinanceDv'])
            ->name('approving.gl-payment-approvals.dv');
        Route::get('/gl-payment-approvals/{id}/lddap-ada', [ApprovingOfficerController::class, 'showGlFinanceLddapAda'])
            ->name('approving.gl-payment-approvals.lddap-ada');
        Route::patch('/gl-payment-approvals/{id}', [ApprovingOfficerController::class, 'updateGlPaymentApproval'])
            ->name('approving.gl-payment-approvals.update');
        Route::get('/gl-program-amount-approvals', [ApprovingOfficerController::class, 'glProgramAmountApprovals'])
            ->name('approving.gl-program-amount-approvals');
        Route::get('/gl-program-amount-approvals/{batchId}/records/{applicationId}', [ApprovingOfficerController::class, 'showGlProgramAmountApprovalBatchRecord'])
            ->name('approving.gl-program-amount-approvals.records.show');
        Route::get('/gl-program-amount-approvals/{id}', [ApprovingOfficerController::class, 'showGlProgramAmountApproval'])
            ->name('approving.gl-program-amount-approvals.show');
        Route::get('/gl-program-amount-approvals/{id}/ors', [ApprovingOfficerController::class, 'showGlFinanceOrs'])
            ->name('approving.gl-program-amount-approvals.ors');
        Route::get('/gl-program-amount-approvals/{id}/dv', [ApprovingOfficerController::class, 'showGlFinanceDv'])
            ->name('approving.gl-program-amount-approvals.dv');
        Route::get('/gl-program-amount-approvals/{id}/lddap-ada', [ApprovingOfficerController::class, 'showGlFinanceLddapAda'])
            ->name('approving.gl-program-amount-approvals.lddap-ada');
        Route::patch('/gl-program-amount-approvals/{id}', [ApprovingOfficerController::class, 'updateGlProgramAmountApproval'])
            ->name('approving.gl-program-amount-approvals.update');

        Route::get('/application/{id}', [ApprovingOfficerController::class, 'show'])
            ->name('approving.show');

        Route::get('/application/{id}/certificate', [ApprovingOfficerController::class, 'certificate'])
            ->name('approving.certificate');

        Route::get('/application/{id}/guarantee-letter', [ApprovingOfficerController::class, 'guaranteeLetter'])
            ->name('approving.guarantee-letter');

        Route::post('/application/{applicationId}/recommendation/{recommendationId}', [ApprovingOfficerController::class, 'updateRecommendation'])
            ->name('approving.recommendations.update');

        Route::delete('/application/{applicationId}/recommendation/{recommendationId}', [ApprovingOfficerController::class, 'destroyRecommendation'])
            ->name('approving.recommendations.destroy');

        Route::post('/application/{id}/approve', [ApprovingOfficerController::class, 'approve'])
            ->name('approving.approve');

        Route::post('/application/{id}/deny', [ApprovingOfficerController::class, 'deny'])
            ->name('approving.deny');

    });
});

Route::middleware(['auth', 'role:budget_officer'])->prefix('budget-officer')->group(function () {
    Route::get('/dashboard', [ApprovingOfficerController::class, 'budgetOfficerDashboard'])
        ->name('budget-officer.dashboard');
    Route::get('/gl-payment-approvals', [ApprovingOfficerController::class, 'glPaymentApprovals'])
        ->name('budget-officer.gl-payment-approvals');
    Route::get('/gl-payment-approvals/{id}', [ApprovingOfficerController::class, 'showGlPaymentApproval'])
        ->name('budget-officer.gl-payment-approvals.show');
    Route::get('/gl-payment-approvals/{id}/ors', [ApprovingOfficerController::class, 'showGlFinanceOrs'])
        ->name('budget-officer.gl-payment-approvals.ors');
    Route::get('/gl-payment-approvals/{id}/dv', [ApprovingOfficerController::class, 'showGlFinanceDv'])
        ->name('budget-officer.gl-payment-approvals.dv');
    Route::get('/gl-payment-approvals/{id}/lddap-ada', [ApprovingOfficerController::class, 'showGlFinanceLddapAda'])
        ->name('budget-officer.gl-payment-approvals.lddap-ada');
    Route::patch('/gl-payment-approvals/{id}', [ApprovingOfficerController::class, 'updateGlPaymentApproval'])
        ->name('budget-officer.gl-payment-approvals.update');
});

Route::middleware(['auth', 'role:budget_approver'])->prefix('budget-approver')->group(function () {
    Route::get('/dashboard', [ApprovingOfficerController::class, 'budgetOfficerDashboard'])
        ->name('budget-approver.dashboard');
    Route::get('/gl-payment-approvals', [ApprovingOfficerController::class, 'glPaymentApprovals'])
        ->name('budget-approver.gl-payment-approvals');
    Route::get('/gl-payment-approvals/{id}', [ApprovingOfficerController::class, 'showGlPaymentApproval'])
        ->name('budget-approver.gl-payment-approvals.show');
    Route::get('/gl-payment-approvals/{batchId}/records/{applicationId}', [ApprovingOfficerController::class, 'showGlPaymentApprovalBatchRecord'])
        ->name('budget-approver.gl-payment-approvals.records.show');
    Route::get('/gl-payment-approvals/{id}/ors', [ApprovingOfficerController::class, 'showGlFinanceOrs'])
        ->name('budget-approver.gl-payment-approvals.ors');
    Route::get('/gl-payment-approvals/{id}/dv', [ApprovingOfficerController::class, 'showGlFinanceDv'])
        ->name('budget-approver.gl-payment-approvals.dv');
    Route::get('/gl-payment-approvals/{id}/lddap-ada', [ApprovingOfficerController::class, 'showGlFinanceLddapAda'])
        ->name('budget-approver.gl-payment-approvals.lddap-ada');
    Route::patch('/gl-payment-approvals/{id}', [ApprovingOfficerController::class, 'updateGlPaymentApproval'])
        ->name('budget-approver.gl-payment-approvals.update');
});

Route::middleware(['auth', 'role:accounting_officer'])->prefix('accounting-officer')->group(function () {
    Route::get('/dashboard', [AccountingController::class, 'accountingOfficerDashboard'])
        ->name('accounting-officer.dashboard');
    Route::get('/gl-payment-reviews', [AccountingController::class, 'accountingOfficerQueue'])
        ->name('accounting-officer.gl-payment-reviews');
    Route::get('/gl-payment-reviews/{application}', [AccountingController::class, 'showAccountingOfficer'])
        ->name('accounting-officer.gl-payment-reviews.show');
    Route::get('/gl-payment-reviews/{application}/ors', [AccountingController::class, 'showAccountingOfficerOrs'])
        ->name('accounting-officer.gl-payment-reviews.ors');
    Route::get('/gl-payment-reviews/{application}/dv', [AccountingController::class, 'showAccountingOfficerDv'])
        ->name('accounting-officer.gl-payment-reviews.dv');
    Route::get('/gl-payment-reviews/{application}/lddap-ada', [AccountingController::class, 'showAccountingOfficerLddapAda'])
        ->name('accounting-officer.gl-payment-reviews.lddap-ada');
    Route::patch('/gl-payment-reviews/{application}', [AccountingController::class, 'submitAccountingOfficerReview'])
        ->name('accounting-officer.gl-payment-reviews.update');
});

Route::middleware(['auth', 'role:accounting_approver'])->prefix('accounting-approver')->group(function () {
    Route::get('/dashboard', [AccountingController::class, 'accountingApproverDashboard'])
        ->name('accounting-approver.dashboard');
    Route::get('/gl-payment-approvals', [AccountingController::class, 'accountingApproverQueue'])
        ->name('accounting-approver.gl-payment-approvals');
    Route::get('/gl-payment-approvals/{batchId}/records/{applicationId}', [AccountingController::class, 'showAccountingApproverBatchRecord'])
        ->name('accounting-approver.gl-payment-approvals.records.show');
    Route::get('/gl-payment-approvals/{id}', [AccountingController::class, 'showAccountingApprover'])
        ->name('accounting-approver.gl-payment-approvals.show');
    Route::get('/gl-payment-approvals/{application}/ors', [AccountingController::class, 'showAccountingApproverOrs'])
        ->name('accounting-approver.gl-payment-approvals.ors');
    Route::get('/gl-payment-approvals/{application}/dv', [AccountingController::class, 'showAccountingApproverDv'])
        ->name('accounting-approver.gl-payment-approvals.dv');
    Route::get('/gl-payment-approvals/{application}/lddap-ada', [AccountingController::class, 'showAccountingApproverLddapAda'])
        ->name('accounting-approver.gl-payment-approvals.lddap-ada');
    Route::patch('/gl-payment-approvals/{id}', [AccountingController::class, 'submitAccountingApproverDecision'])
        ->name('accounting-approver.gl-payment-approvals.update');
    Route::get('/cash-certifications', [AccountingController::class, 'cashCertificationQueue'])
        ->name('accounting-approver.cash-certifications');
    Route::get('/cash-certifications/{batchId}/records/{applicationId}', [AccountingController::class, 'showCashCertificationBatchRecord'])
        ->name('accounting-approver.cash-certifications.records.show');
    Route::get('/cash-certifications/{id}', [AccountingController::class, 'showCashCertification'])
        ->name('accounting-approver.cash-certifications.show');
    Route::get('/cash-certifications/{application}/ors', [AccountingController::class, 'showCashCertificationOrs'])
        ->name('accounting-approver.cash-certifications.ors');
    Route::get('/cash-certifications/{application}/dv', [AccountingController::class, 'showCashCertificationDv'])
        ->name('accounting-approver.cash-certifications.dv');
    Route::get('/cash-certifications/{application}/lddap-ada', [AccountingController::class, 'showCashCertificationLddapAda'])
        ->name('accounting-approver.cash-certifications.lddap-ada');
    Route::patch('/cash-certifications/{id}', [AccountingController::class, 'submitCashCertificationDecision'])
        ->name('accounting-approver.cash-certifications.update');
});

Route::middleware(['auth', 'role:cash_officer'])->prefix('cash-officer')->group(function () {
    Route::get('/dashboard', [CashController::class, 'cashOfficerDashboard'])
        ->name('cash-officer.dashboard');
    Route::get('/gl-payment-reviews', [CashController::class, 'cashOfficerQueue'])
        ->name('cash-officer.gl-payment-reviews');
    Route::get('/gl-payment-reviews/{application}', [CashController::class, 'showCashOfficer'])
        ->name('cash-officer.gl-payment-reviews.show');
    Route::get('/gl-payment-reviews/{application}/ors', [CashController::class, 'showCashOfficerOrs'])
        ->name('cash-officer.gl-payment-reviews.ors');
    Route::get('/gl-payment-reviews/{application}/dv', [CashController::class, 'showCashOfficerDv'])
        ->name('cash-officer.gl-payment-reviews.dv');
    Route::get('/gl-payment-reviews/{application}/lddap-ada', [CashController::class, 'showCashOfficerLddapAda'])
        ->name('cash-officer.gl-payment-reviews.lddap-ada');
    Route::patch('/gl-payment-reviews/{application}', [CashController::class, 'submitCashOfficerReview'])
        ->name('cash-officer.gl-payment-reviews.update');
});

Route::middleware(['auth', 'role:cash_approver'])->prefix('cash-approver')->group(function () {
    Route::get('/dashboard', [CashController::class, 'cashApproverDashboard'])
        ->name('cash-approver.dashboard');
    Route::get('/gl-payment-approvals', [CashController::class, 'cashApproverQueue'])
        ->name('cash-approver.gl-payment-approvals');
    Route::get('/gl-payment-approvals/{batchId}/records/{applicationId}', [CashController::class, 'showCashApproverBatchRecord'])
        ->name('cash-approver.gl-payment-approvals.records.show');
    Route::get('/gl-payment-approvals/{id}', [CashController::class, 'showCashApprover'])
        ->name('cash-approver.gl-payment-approvals.show');
    Route::get('/gl-payment-approvals/{application}/ors', [CashController::class, 'showCashApproverOrs'])
        ->name('cash-approver.gl-payment-approvals.ors');
    Route::get('/gl-payment-approvals/{application}/dv', [CashController::class, 'showCashApproverDv'])
        ->name('cash-approver.gl-payment-approvals.dv');
    Route::get('/gl-payment-approvals/{application}/lddap-ada', [CashController::class, 'showCashApproverLddapAda'])
        ->name('cash-approver.gl-payment-approvals.lddap-ada');
    Route::patch('/gl-payment-approvals/{id}', [CashController::class, 'submitCashApproverDecision'])
        ->name('cash-approver.gl-payment-approvals.update');
});

Route::middleware(['auth', 'role:finance_director'])->prefix('finance-director')->group(function () {
    Route::get('/dashboard', [FinanceDirectorController::class, 'dashboard'])
        ->name('finance-director.dashboard');
    Route::get('/gl-payment-approvals', [FinanceDirectorController::class, 'queue'])
        ->name('finance-director.gl-payment-approvals');
    Route::get('/gl-payment-approvals/{batchId}/records/{applicationId}', [FinanceDirectorController::class, 'showBatchRecord'])
        ->name('finance-director.gl-payment-approvals.records.show');
    Route::get('/gl-payment-approvals/{application}/ors', [FinanceDirectorController::class, 'showOrs'])
        ->name('finance-director.gl-payment-approvals.ors');
    Route::get('/gl-payment-approvals/{application}/dv', [FinanceDirectorController::class, 'showDv'])
        ->name('finance-director.gl-payment-approvals.dv');
    Route::get('/gl-payment-approvals/{application}/lddap-ada', [FinanceDirectorController::class, 'showLddapAda'])
        ->name('finance-director.gl-payment-approvals.lddap-ada');
    Route::get('/gl-payment-approvals/{id}', [FinanceDirectorController::class, 'show'])
        ->name('finance-director.gl-payment-approvals.show');
    Route::patch('/gl-payment-approvals/{id}', [FinanceDirectorController::class, 'update'])
        ->name('finance-director.gl-payment-approvals.update');
});

require __DIR__.'/auth.php';
