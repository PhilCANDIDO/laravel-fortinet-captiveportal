<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('admin')->user();
        
        if (!$user) {
            return redirect()->route('admin.login');
        }
        
        if ($user->google2fa_enabled && !session('mfa_verified')) {
            return redirect()->route('admin.mfa.verify');
        }
        
        return $next($request);
    }
}