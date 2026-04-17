<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ClientDashboardController;

Route::get('/', function () {
    return view('welcome');
});

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
});


/*
|--------------------------------------------------------------------------
| CLIENT ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:client'])->group(function () {

    Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])
        ->name('client.dashboard');

    Route::get('/client/application', [ApplicationController::class, 'create']);

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

    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    });

    Route::get('/admin/applications', function () {
        return view('admin.applications');
    });

    Route::get('/admin/approvals', function () {
        return view('admin.approvals');
    });

    Route::get('/admin/release', function () {
        return view('admin.release');
    });

});

/*
|--------------------------------------------------------------------------
| SOCIAL WORKER ROUTES 
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\SocialWorkerController;

Route::middleware(['auth'])->group(function () {

    Route::get('/social-worker/dashboard', [SocialWorkerController::class, 'dashboard']);
    Route::get('/social-worker/applications', [SocialWorkerController::class, 'applications'])
    ->name('socialworker.applications');
    Route::get('/social-worker/application/{id}', [SocialWorkerController::class, 'show']);
    Route::get('/social-worker/application/{id}/assess', [SocialWorkerController::class, 'assess']);
    Route::post('/social-worker/application/{id}/assess', [SocialWorkerController::class, 'updateAssessment'])
    ->name('socialworker.assess.update');
    Route::get('/social-worker/application/{id}/intake', [SocialWorkerController::class, 'intake'])
    ->name('socialworker.intake');
    Route::post('/social-worker/application/{id}/intake', [SocialWorkerController::class, 'saveIntake'])
        ->name('socialworker.intake.save');
    Route::get('/social-worker/application/{id}/show',
    [SocialWorkerController::class, 'show'])
    ->name('socialworker.show');
    Route::get('/social-worker/application/{id}/certificate',
    [SocialWorkerController::class, 'certificate'])
    ->name('socialworker.certificate');
    Route::post('/social-worker/application/{id}/release',
    [SocialWorkerController::class, 'release'])
    ->name('socialworker.release');
});
/*
|--------------------------------------------------------------------------
| APPROVING OFFICER ROUTES 
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\ApprovingOfficerController;

Route::middleware(['auth'])->group(function () {
    Route::prefix('approving-officer')->group(function () {

    Route::get('/dashboard', [App\Http\Controllers\ApprovingOfficerController::class, 'dashboard'])
        ->name('approving.dashboard');

    Route::get('/applications', [App\Http\Controllers\ApprovingOfficerController::class, 'applications'])
        ->name('approving.applications');

    Route::get('/application/{id}', [App\Http\Controllers\ApprovingOfficerController::class, 'show'])
        ->name('approving.show');

    Route::post('/application/{id}/approve', [App\Http\Controllers\ApprovingOfficerController::class, 'approve'])
        ->name('approving.approve');

    Route::post('/application/{id}/deny', [App\Http\Controllers\ApprovingOfficerController::class, 'deny'])
        ->name('approving.deny');

    });
});

require __DIR__.'/auth.php';