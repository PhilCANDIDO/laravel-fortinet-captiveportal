<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordExpiration
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('admin')->user();
        
        if ($user && $user->isPasswordExpired()) {
            if (!$request->routeIs('admin.password.change', 'admin.password.update', 'admin.logout')) {
                return redirect()->route('admin.password.change')
                    ->with('warning', 'Your password has expired. Please change it to continue.');
            }
        }
        
        return $next($request);
    }
}