@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <!-- Success Header -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-8 py-6">
                <div class="flex items-center justify-center">
                    <svg class="w-16 h-16 text-white mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h2 class="text-3xl font-bold text-white">
                            {{ __('guest.registration_success_title') }}
                        </h2>
                        <p class="text-green-100 mt-1">
                            {{ __('guest.registration_success_message', ['email' => $email]) }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="px-8 py-8">
                <!-- Account Credentials -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        {{ __('guest.account_credentials') }}
                    </h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('guest.username_label') }} (FortiGate):
                            </label>
                            <div class="bg-white border border-gray-300 rounded-md px-3 py-2 font-mono text-sm flex items-center justify-between">
                                <span id="username">{{ $username }}</span>
                                <button onclick="copyToClipboard('username')" class="ml-2 text-blue-600 hover:text-blue-800" title="Copier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Utilisez ce nom d'utilisateur pour vous connecter au portail captif</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('guest.password_label') }}:
                            </label>
                            <div class="bg-white border border-gray-300 rounded-md px-3 py-2 font-mono text-sm flex items-center justify-between">
                                <span id="password">{{ $password }}</span>
                                <button onclick="copyToClipboard('password')" class="ml-2 text-blue-600 hover:text-blue-800" title="Copier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                        <div class="flex">
                            <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="ml-3 text-sm text-yellow-700">
                                {{ __('guest.save_credentials_notice') }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Validation Notice -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-amber-900 mb-2">
                        {{ __('guest.important_notice') }}
                    </h3>
                    <p class="text-amber-700">
                        {{ __('guest.validation_time_limit') }}
                    </p>
                </div>
                
                <!-- Next Steps -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ __('guest.next_steps_title') }}
                    </h3>
                    <ol class="list-decimal list-inside space-y-2 text-gray-700">
                        <li>{{ __('guest.step_check_email') }}</li>
                        <li>{{ __('guest.step_click_link') }}</li>
                        <li>{{ __('guest.step_accept_charter') }}</li>
                        <li>{{ __('guest.step_connect') }}</li>
                    </ol>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    @if($captivePortalUrl)
                    <a href="{{ $captivePortalUrl }}" target="_blank"
                       class="flex-1 text-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 inline-flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        {{ __('guest.open_captive_portal') }}
                    </a>
                    @endif
                    
                    <a href="{{ route('guest.register') }}"
                       class="flex-1 text-center px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        {{ __('guest.register_another') }}
                    </a>
                </div>
                
                <!-- Help Notice -->
                <div class="mt-6 text-center text-sm text-gray-600">
                    <p>{{ __('guest.spam_notice') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.innerText;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            // Show copied feedback
            const button = element.nextElementSibling;
            const originalHTML = button.innerHTML;
            button.innerHTML = '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
            }, 2000);
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
}
</script>
@endsection