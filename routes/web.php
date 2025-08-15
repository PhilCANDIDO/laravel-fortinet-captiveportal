<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('guest.register');
});

// Locale change route
Route::get('/locale/{locale}', [App\Http\Controllers\LocaleController::class, 'setLocale'])->name('locale.change');

// Fallback login route for default Laravel auth
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Include admin routes
require __DIR__.'/admin.php';

// Include guest routes
require __DIR__.'/guest.php';
