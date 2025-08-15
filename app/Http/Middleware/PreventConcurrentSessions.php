<?php

namespace App\Http\Middleware;

use App\Models\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreventConcurrentSessions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if concurrent session prevention is disabled
        if (config('app.env') === 'local' || config('auth.prevent_concurrent_sessions', true) === false) {
            return $next($request);
        }
        
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
            $currentSessionId = session()->getId();
            
            // Check if current session is still active in database
            $session = AdminSession::where('admin_user_id', $user->id)
                ->where('id', $currentSessionId)
                ->where('is_active', true)
                ->first();
            
            if (!$session) {
                // Clean up old sessions that are older than 24 hours (likely from container restarts)
                AdminSession::where('admin_user_id', $user->id)
                    ->where('last_activity', '<', \Carbon\Carbon::now()->subDay()->timestamp)
                    ->delete();
                
                // Check if ANY active session exists for this user that was active in the last hour
                $hasRecentActiveSession = AdminSession::where('admin_user_id', $user->id)
                    ->where('is_active', true)
                    ->where('last_activity', '>', \Carbon\Carbon::now()->subHour()->timestamp)
                    ->exists();
                
                if ($hasRecentActiveSession) {
                    // Another recent session exists, so this one was invalidated
                    Auth::guard('admin')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    return redirect()->route('admin.login')
                        ->with('warning', __('auth.session_invalidated'));
                } else {
                    // No recent session exists, deactivate old ones and create new one
                    AdminSession::where('admin_user_id', $user->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);
                    
                    AdminSession::create([
                        'id' => $currentSessionId,
                        'admin_user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'payload' => '',
                        'last_activity' => \Carbon\Carbon::now()->timestamp,
                        'is_active' => true,
                    ]);
                }
            } else {
                // Update last activity
                $session->touch();
            }
        }
        
        return $next($request);
    }
}