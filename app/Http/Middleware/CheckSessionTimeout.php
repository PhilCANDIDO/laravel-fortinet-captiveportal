<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class CheckSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('admin')->user();
        
        if ($user) {
            $lastActivity = session('last_activity');
            $timeout = 15 * 60; // 15 minutes in seconds
            
            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                auth('admin')->logout();
                session()->flush();
                
                return redirect()->route('admin.login')
                    ->with('warning', 'Your session has expired due to inactivity.');
            }
            
            session(['last_activity' => time()]);
        }
        
        return $next($request);
    }
}