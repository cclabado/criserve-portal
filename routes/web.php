<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ClientDashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/client/dashboard', function () {
    return view('client.dashboard');
})->middleware(['auth']);

Route::get('/client/application', [ApplicationController::class, 'create'])->middleware('auth');

Route::post('/client/application', [ApplicationController::class, 'store'])->middleware('auth');

Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('client.dashboard');

require __DIR__.'/auth.php';
