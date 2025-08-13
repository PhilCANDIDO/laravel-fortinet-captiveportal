<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $availableLocales = ['fr', 'en', 'it', 'es'];
        $defaultLocale = config('app.locale', 'fr');
        
        // Check if locale is in URL
        if ($request->has('locale') && in_array($request->locale, $availableLocales)) {
            $locale = $request->locale;
            Session::put('locale', $locale);
        }
        // Check if locale is in session
        elseif (Session::has('locale') && in_array(Session::get('locale'), $availableLocales)) {
            $locale = Session::get('locale');
        }
        // Check browser language
        elseif ($request->hasHeader('Accept-Language')) {
            $browserLang = substr($request->header('Accept-Language'), 0, 2);
            $locale = in_array($browserLang, $availableLocales) ? $browserLang : $defaultLocale;
        }
        // Use default
        else {
            $locale = $defaultLocale;
        }
        
        App::setLocale($locale);
        
        return $next($request);
    }
}