<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SocialWorkerGoogleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\ServiceProviderController;

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
    Route::get('/admin/libraries', [AdminController::class, 'libraries'])
        ->name('admin.libraries');
    Route::get('/admin/libraries/{library}', [AdminController::class, 'showLibrary'])
        ->name('admin.libraries.show');
    Route::get('/admin/frequency-rules', [AdminController::class, 'frequencyRules'])
        ->name('admin.frequency-rules');
    Route::get('/admin/users', [AdminController::class, 'users'])
        ->name('admin.users');
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
    Route::post('/admin/libraries/service-providers', [AdminController::class, 'storeServiceProvider'])
        ->name('admin.libraries.service-providers.store');
    Route::post('/admin/libraries/positions', [AdminController::class, 'storePosition'])
        ->name('admin.libraries.positions.store');
    Route::post('/admin/libraries/relationships', [AdminController::class, 'storeRelationship'])
        ->name('admin.libraries.relationships.store');
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

/*
|--------------------------------------------------------------------------
| SOCIAL WORKER ROUTES 
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\SocialWorkerController;

Route::middleware(['auth', 'role:social_worker'])->group(function () {

    Route::get('/social-worker/dashboard', [SocialWorkerController::class, 'dashboard']);
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
    Route::post('/service-provider/guarantee-letters/{application}/statement', [ServiceProviderController::class, 'uploadUpdatedStatement'])
        ->name('service-provider.statement.upload');
});
/*
|--------------------------------------------------------------------------
| APPROVING OFFICER ROUTES 
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\ApprovingOfficerController;

Route::middleware(['auth', 'role:approving_officer'])->group(function () {
    Route::prefix('approving-officer')->group(function () {

    Route::get('/dashboard', [App\Http\Controllers\ApprovingOfficerController::class, 'dashboard'])
        ->name('approving.dashboard');

    Route::get('/applications', [App\Http\Controllers\ApprovingOfficerController::class, 'applications'])
        ->name('approving.applications');

    Route::get('/my-approvals', [App\Http\Controllers\ApprovingOfficerController::class, 'myApprovals'])
        ->name('approving.my-approvals');

    Route::get('/application/{id}', [App\Http\Controllers\ApprovingOfficerController::class, 'show'])
        ->name('approving.show');

    Route::get('/application/{id}/certificate', [App\Http\Controllers\ApprovingOfficerController::class, 'certificate'])
        ->name('approving.certificate');

    Route::get('/application/{id}/guarantee-letter', [App\Http\Controllers\ApprovingOfficerController::class, 'guaranteeLetter'])
        ->name('approving.guarantee-letter');

    Route::post('/application/{applicationId}/recommendation/{recommendationId}', [App\Http\Controllers\ApprovingOfficerController::class, 'updateRecommendation'])
        ->name('approving.recommendations.update');

    Route::delete('/application/{applicationId}/recommendation/{recommendationId}', [App\Http\Controllers\ApprovingOfficerController::class, 'destroyRecommendation'])
        ->name('approving.recommendations.destroy');

    Route::post('/application/{id}/approve', [App\Http\Controllers\ApprovingOfficerController::class, 'approve'])
        ->name('approving.approve');

    Route::post('/application/{id}/deny', [App\Http\Controllers\ApprovingOfficerController::class, 'deny'])
        ->name('approving.deny');

    });
});

require __DIR__.'/auth.php';
