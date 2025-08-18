@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl w-full">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-12 text-center">
                <div class="mb-4">
                    <svg class="w-20 h-20 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($isAuthenticated && !$hasError)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                        @endif
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2">
                    @if($isAuthenticated && !$hasError)
                        {{ __('landing.connected_title') }}
                    @else
                        {{ __('landing.welcome_title') }}
                    @endif
                </h1>
                <p class="text-xl text-blue-100">
                    {{ config('app.name') }}
                </p>
            </div>
            
            <div class="px-8 py-8">
                @if($hasError)
                    <!-- Error Message -->
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    {{ __('landing.error_title') }}
                                </h3>
                                <p class="text-sm text-red-700 mt-1">
                                    {{ $fortigateData['error_message'] ?? __('landing.error_generic') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if($isAuthenticated && !$hasError)
                    <!-- Success Message -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-semibold text-gray-900 mb-2">
                            {{ __('landing.connection_successful') }}
                        </h2>
                        <p class="text-gray-600">
                            {{ __('landing.connection_message') }}
                        </p>
                        
                        @if(!empty($fortigateData['username']))
                        <div class="mt-4 inline-flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            {{ __('landing.connected_as') }}: <span class="font-medium ml-1">{{ $fortigateData['username'] }}</span>
                        </div>
                        @endif
                        
                        @if(!empty($fortigateData['ssid']))
                        <div class="mt-2 inline-flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                            {{ __('landing.network') }}: <span class="font-medium ml-1">{{ $fortigateData['ssid'] }}</span>
                        </div>
                        @endif
                    </div>
                @else
                    <!-- Welcome Message -->
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">
                            {{ __('landing.welcome_message') }}
                        </h2>
                        <p class="text-gray-600 mb-6">
                            {{ __('landing.portal_description') }}
                        </p>
                    </div>
                @endif
                
                <!-- Quick Links -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <a href="https://www.google.com" target="_blank" class="group bg-gray-50 rounded-lg p-6 hover:bg-gray-100 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                            </svg>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900">{{ __('landing.internet_access') }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ __('landing.browse_web') }}</p>
                    </a>
                    
                    <a href="{{ route('guest.register') }}" class="group bg-gray-50 rounded-lg p-6 hover:bg-gray-100 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900">{{ __('landing.guest_registration') }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ __('landing.register_new_guest') }}</p>
                    </a>
                    
                    <a href="#" onclick="showSupport()" class="group bg-gray-50 rounded-lg p-6 hover:bg-gray-100 transition-colors cursor-pointer">
                        <div class="flex items-center justify-between mb-3">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900">{{ __('landing.support') }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ __('landing.get_help') }}</p>
                    </a>
                </div>
                
                <!-- Language Selector -->
                <div class="text-center pt-6 border-t border-gray-200">
                    <div class="inline-flex items-center space-x-4">
                        <span class="text-sm text-gray-500">{{ __('landing.select_language') }}:</span>
                        <div class="flex space-x-2">
                            <a href="{{ url()->current() }}?locale=fr" 
                               class="px-3 py-1 text-sm rounded {{ App::getLocale() === 'fr' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                Français
                            </a>
                            <a href="{{ url()->current() }}?locale=en" 
                               class="px-3 py-1 text-sm rounded {{ App::getLocale() === 'en' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                English
                            </a>
                            <a href="{{ url()->current() }}?locale=it" 
                               class="px-3 py-1 text-sm rounded {{ App::getLocale() === 'it' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                Italiano
                            </a>
                            <a href="{{ url()->current() }}?locale=es" 
                               class="px-3 py-1 text-sm rounded {{ App::getLocale() === 'es' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                Español
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-6 text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('landing.all_rights_reserved') }}</p>
        </div>
    </div>
</div>

<!-- Support Modal -->
<div id="supportModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            {{ __('landing.support_title') }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                {{ __('landing.support_message') }}
                            </p>
                            <div class="mt-4 space-y-2">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm">support@example.com</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span class="text-sm">+33 1 23 45 67 89</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="hideSupport()" 
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('landing.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showSupport() {
    document.getElementById('supportModal').classList.remove('hidden');
}

function hideSupport() {
    document.getElementById('supportModal').classList.add('hidden');
}

// Log page view if debug mode
@if(config('app.debug'))
console.log('FortiGate Landing Page Data:', @json($fortigateData));
@endif
</script>
@endsection