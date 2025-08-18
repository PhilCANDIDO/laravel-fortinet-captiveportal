<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class LandingController extends Controller
{
    /**
     * Display the landing page after FortiGate authentication
     * This is the page users are redirected to after successful network authentication
     */
    public function index(Request $request)
    {
        // Log all parameters if in debug mode
        if (config('app.debug')) {
            Log::debug('FortiGate post-authentication redirect', [
                'all_parameters' => $request->all(),
                'query_parameters' => $request->query(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
        }
        
        // Extract common FortiGate parameters
        $fortigateData = [
            'username' => $request->input('username'),
            'success' => $request->input('success'),
            'error' => $request->input('error'),
            'error_message' => $request->input('error_message'),
            'session_id' => $request->input('session_id'),
            'client_ip' => $request->input('client_ip'),
            'client_mac' => $request->input('client_mac'),
            'ssid' => $request->input('ssid'),
            'ap_mac' => $request->input('ap_mac'),
            'redirect_url' => $request->input('redirect_url'),
        ];
        
        // Filter out null values
        $fortigateData = array_filter($fortigateData, function($value) {
            return $value !== null;
        });
        
        // Set locale from request or session
        if ($request->has('locale')) {
            $locale = $request->input('locale');
            if (in_array($locale, config('app.available_locales', ['fr', 'en']))) {
                App::setLocale($locale);
                session(['locale' => $locale]);
            }
        } elseif (session()->has('locale')) {
            App::setLocale(session('locale'));
        }
        
        return view('landing.index', [
            'fortigateData' => $fortigateData,
            'isAuthenticated' => !empty($fortigateData['username']) || $request->input('success') === 'true',
            'hasError' => !empty($fortigateData['error']) || !empty($fortigateData['error_message']),
        ]);
    }
}