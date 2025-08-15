<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestRegistrationController;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
|
| Routes for guest registration and validation
|
*/

Route::prefix('guest')->name('guest.')->group(function () {
    // Registration
    Route::get('/register', [GuestRegistrationController::class, 'showForm'])->name('register');
    Route::post('/register', [GuestRegistrationController::class, 'register']);
    Route::get('/register/success', [GuestRegistrationController::class, 'success'])->name('register.success');
    
    // Validation
    Route::get('/validate/{token}', [GuestRegistrationController::class, 'validateEmail'])->name('validate');
    
    // Charter
    Route::get('/charter', [GuestRegistrationController::class, 'showCharter'])->name('charter.show');
    Route::post('/charter/accept', [GuestRegistrationController::class, 'acceptCharter'])->name('charter.accept');
    
    // Portal
    Route::get('/portal', [GuestRegistrationController::class, 'portal'])->name('portal');
});