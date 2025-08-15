@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-green-600">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-12 w-12 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg leading-6 font-medium text-white">
                            {{ __('guest.validation_success_title') }}
                        </h3>
                    </div>
                </div>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
                <div class="text-center">
                    <p class="text-gray-700 mb-4">
                        {{ __('guest.validation_success_message') }}
                    </p>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 text-left">
                                <h3 class="text-sm font-medium text-blue-800">
                                    {{ __('guest.account_details') }}
                                </h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p><strong>{{ __('guest.valid_until') }}:</strong> {{ $user->expires_at->format('d/m/Y H:i') }}</p>
                                    <p><strong>{{ __('guest.account_type') }}:</strong> {{ __('user_types.guest') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-gray-700 mb-6">
                        {{ __('guest.charter_required') }}
                    </p>
                    
                    <a href="{{ $charterUrl }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('guest.view_charter_button') }}
                        <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    
                    <div class="mt-6 p-4 bg-gray-50 rounded-md">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">
                            {{ __('guest.connection_info') }}
                        </h4>
                        <p class="text-sm text-gray-600">
                            {{ __('guest.connection_instructions') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection