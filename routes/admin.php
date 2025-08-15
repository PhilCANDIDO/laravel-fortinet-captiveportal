<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Mfa\Verify as MfaVerify;
use App\Livewire\Admin\Dashboard;
use App\Http\Controllers\Admin\AuthController;

// Add a redirect from /admin to /admin/dashboard
Route::get('/admin', fn() => redirect()->route('admin.dashboard'));

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', Login::class)->name('login');
        Route::get('/password/request', ForgotPassword::class)->name('password.request');
        Route::get('/password/reset/{token}', function($token) {
            return view('admin.auth.reset-password', ['token' => $token]);
        })->name('password.reset')->middleware('signed');
    });
    
    // MFA verification route (authenticated but not MFA verified)
    Route::middleware('auth:admin')->group(function () {
        Route::get('/mfa/verify', MfaVerify::class)->name('mfa.verify');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/session/extend', [AuthController::class, 'extendSession'])->name('session.extend');
        // Password change for first login (before concurrent session check)
        Route::get('/password/change', \App\Livewire\Admin\Auth\ChangePassword::class)->name('password.change');
    });
    
    // Authenticated and MFA verified routes
    Route::middleware(['auth:admin', 'prevent.concurrent', 'check.mfa', 'check.session.timeout', 'check.password.expiration'])->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/', fn() => redirect()->route('admin.dashboard'));
        
        // MFA Management
        Route::prefix('mfa')->name('mfa.')->group(function () {
            Route::get('/setup', function() {
                return view('admin.mfa.setup');
            })->name('setup');
            Route::get('/regenerate-codes', function() {
                return view('admin.mfa.regenerate-codes');
            })->name('regenerate-codes');
        });
        
        // Profile
        Route::get('/profile', \App\Livewire\Admin\Profile\Edit::class)->name('profile');
        
        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', \App\Livewire\Admin\Users\Index::class)->name('index'); // Admin users
            Route::get('/admins', \App\Livewire\Admin\Users\Index::class)->name('admins'); // Admin users (alias)
            Route::get('/guests', \App\Livewire\Admin\GuestManagement::class)->name('guests');
            Route::get('/consultants', \App\Livewire\Admin\ConsultantManagement::class)->name('consultants');
            Route::get('/employees', \App\Livewire\Admin\EmployeeManagement::class)->name('employees');
        });
        
        // Audit Logs (placeholder)
        Route::prefix('audit')->name('audit.')->group(function () {
            Route::get('/', function() {
                return view('admin.audit.index');
            })->name('index');
        });
        
        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', \App\Livewire\Admin\Settings\Index::class)->name('index');
            Route::get('/fortigate', \App\Livewire\Admin\FortiGateSettings::class)->name('fortigate');
        });
    });
});