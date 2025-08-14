<?php

namespace App\Livewire\Admin;

use App\Models\AdminUser;
use App\Models\User;
use App\Models\AuditLog;
use Livewire\Component;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $totalAdmins;
    public $totalUsers;
    public $recentLogins;
    public $failedLogins;
    public $activeSessions;
    
    public function mount()
    {
        $this->totalAdmins = AdminUser::count();
        $this->totalUsers = User::count();
        
        $this->recentLogins = AuditLog::where('event_type', 'login')
            ->where('status', 'success')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
            
        $this->failedLogins = AuditLog::where('event_type', 'login_failed')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();
            
        $this->activeSessions = \App\Models\AdminSession::where('is_active', true)
            ->where('last_activity', '>=', Carbon::now()->subMinutes(15)->timestamp)
            ->count();
    }
    
    public function render()
    {
        $recentActivity = AuditLog::orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        return view('livewire.admin.dashboard', [
            'recentActivity' => $recentActivity
        ])->layout('layouts.admin');
    }
}