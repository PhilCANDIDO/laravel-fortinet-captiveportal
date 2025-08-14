<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fallback login route for default Laravel auth
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Include admin routes
require __DIR__.'/admin.php';
