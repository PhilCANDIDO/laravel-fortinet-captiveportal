<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    public function setLocale($locale, Request $request)
    {
        // Validate locale
        $supportedLocales = ['fr', 'en', 'it', 'es'];
        
        if (!in_array($locale, $supportedLocales)) {
            $locale = 'fr'; // Default to French
        }
        
        // Store locale in session
        Session::put('locale', $locale);
        
        // Set application locale
        app()->setLocale($locale);
        
        // Redirect back or to the specified redirect URL
        $redirectUrl = $request->get('redirect', '/');
        
        return redirect($redirectUrl);
    }
}