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
    Route::get('/social-worker/applications', [SocialWorkerController::class, 'applications']);
    Route::get('/social-worker/application/{id}', [SocialWorkerController::class, 'show']);
    Route::get('/social-worker/application/{id}/assess', [SocialWorkerController::class, 'assess']);
});

require __DIR__.'/auth.php';