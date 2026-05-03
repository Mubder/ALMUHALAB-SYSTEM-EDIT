<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('service-requests.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ServiceRequest recycle bin routes (must come before resource routes)
    Route::get('service-requests/trash', [ServiceRequestController::class, 'trash'])->name('service-requests.trash');
    Route::get('service-requests/{id}/trashed', [ServiceRequestController::class, 'showTrashed'])->name('service-requests.showTrashed');
    Route::post('service-requests/{id}/restore', [ServiceRequestController::class, 'restore'])->name('service-requests.restore');
    Route::delete('service-requests/{id}/force-delete', [ServiceRequestController::class, 'forceDelete'])->name('service-requests.forceDelete');

    // ServiceRequest resource routes
    Route::resource('service-requests', ServiceRequestController::class);
});

require __DIR__.'/auth.php';

