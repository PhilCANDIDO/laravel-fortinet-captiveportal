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
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
            $currentSessionId = session()->getId();
            
            // Check if current session is still active in database
            $session = AdminSession::where('admin_user_id', $user->id)
                ->where('id', $currentSessionId)
                ->where('is_active', true)
                ->first();
            
            if (!$session) {
                // Check if ANY active session exists for this user
                $hasActiveSession = AdminSession::where('admin_user_id', $user->id)
                    ->where('is_active', true)
                    ->exists();
                
                if ($hasActiveSession) {
                    // Another session exists, so this one was invalidated
                    Auth::guard('admin')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    return redirect()->route('admin.login')
                        ->with('warning', __('auth.session_invalidated'));
                } else {
                    // No session exists at all, create one (happens during first login flow)
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