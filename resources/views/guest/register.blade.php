@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" x-data="{ showCharter: false, charterAccepted: false, isSubmitting: false }">
    <div class="max-w-2xl w-full">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                <h2 class="text-3xl font-bold text-white text-center">
                    {{ __('guest.registration_title') }}
                </h2>
                <p class="text-center text-blue-100 text-base mt-2">
                    {{ __('guest.registration_subtitle') }}
                </p>
            </div>
            
            <div class="px-8 py-8">
                @if(isset($portalInfo) && $portalInfo)
                    <div class="mb-6 p-4 text-sm text-blue-800 rounded-lg bg-blue-50 border border-blue-200">
                        <div class="flex items-center">
                            @if($portalInfo['network_type'] === 'wireless')
                                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17.778 8.222c-4.296-4.296-11.26-4.296-15.556 0A1 1 0 01.808 6.808c5.076-5.077 13.308-5.077 18.384 0a1 1 0 01-1.414 1.414zM14.95 11.05a7 7 0 00-9.9 0 1 1 0 01-1.414-1.414 9 9 0 0112.728 0 1 1 0 01-1.414 1.414zM12.12 13.88a3 3 0 00-4.242 0 1 1 0 01-1.415-1.415 5 5 0 017.072 0 1 1 0 01-1.415 1.415zM9 16a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z"/>
                                </svg>
                            @else
                                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z"/><path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z"/><path d="M17 5c0 1.657-3.134 3-7 3S3 6.657 3 5s3.134-3 7-3 7 1.343 7 3z"/>
                                </svg>
                            @endif
                            <div>
                                <span class="font-medium">{{ __('guest.connected_network') }}: {{ $portalInfo['network_name'] }}</span>
                                @if($portalInfo['client_ip'] !== 'N/A')
                                    <span class="ml-2 text-xs">(IP: {{ $portalInfo['client_ip'] }})</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                
                <form action="{{ route('guest.register') }}" method="POST" @submit.prevent="if (!charterAccepted) { showCharter = true; $event.preventDefault(); } else { $el.submit(); }">
                    @csrf
                    
                    @if($errors->any())
                        <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200" role="alert">
                            <div class="flex items-center mb-2">
                                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                                </svg>
                                <span class="font-medium">{{ __('validation.errors_occurred') }}</span>
                            </div>
                            <ul class="mt-1.5 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <div class="space-y-6">
                        <!-- Personal Information Section -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('guest.personal_info') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block mb-2 text-sm font-medium text-gray-900">
                                        {{ __('guest.first_name') }} <span class="text-red-500">*</span>
                                    </label>
                                    <x-input id="first_name" name="first_name" type="text" required
                                             placeholder="{{ __('guest.first_name_placeholder') }}"
                                             value="{{ old('first_name') }}" />
                                </div>
                                <div>
                                    <label for="last_name" class="block mb-2 text-sm font-medium text-gray-900">
                                        {{ __('guest.last_name') }} <span class="text-red-500">*</span>
                                    </label>
                                    <x-input id="last_name" name="last_name" type="text" required
                                             placeholder="{{ __('guest.last_name_placeholder') }}"
                                             value="{{ old('last_name') }}" />
                                </div>
                                <div>
                                    <label for="email" class="block mb-2 text-sm font-medium text-gray-900">
                                        {{ __('guest.email') }} <span class="text-red-500">*</span>
                                    </label>
                                    <x-input id="email" name="email" type="email" required
                                             placeholder="{{ __('guest.email_placeholder') }}"
                                             value="{{ old('email') }}" />
                                </div>
                                <div>
                                    <label for="phone" class="block mb-2 text-sm font-medium text-gray-900">
                                        {{ __('guest.phone') }}
                                    </label>
                                    <x-input id="phone" name="phone" type="tel"
                                             placeholder="{{ __('guest.phone_placeholder') }}"
                                             value="{{ old('phone') }}" />
                                </div>
                                <div class="md:col-span-2">
                                    <label for="company_name" class="block mb-2 text-sm font-medium text-gray-900">
                                        {{ __('guest.company') }}
                                    </label>
                                    <x-input id="company_name" name="company_name" type="text"
                                             placeholder="{{ __('guest.company_placeholder') }}"
                                             value="{{ old('company_name') }}" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Visit Reason Section -->
                        <div>
                            <label for="visit_reason" class="block mb-2 text-sm font-medium text-gray-900">
                                {{ __('guest.visit_reason') }}
                            </label>
                            <x-textarea id="visit_reason" name="visit_reason" rows="3"
                                        placeholder="{{ __('guest.visit_reason_placeholder') }}">{{ old('visit_reason') }}</x-textarea>
                        </div>
                        
                        <!-- Validation Notice -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">
                                        {{ __('guest.important_notice') }}
                                    </h3>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        {{ __('guest.validation_notice') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div>
                            <button type="submit"
                                    :disabled="isSubmitting"
                                    class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 text-center inline-flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                                <span x-show="!isSubmitting">{{ __('guest.register_button') }}</span>
                                <span x-show="isSubmitting">{{ __('guest.processing') }}...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Charter Modal -->
    <div x-show="showCharter" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="showCharter"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 aria-hidden="true"></div>

            <!-- This element is to trick the browser into centering the modal contents. -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div x-show="showCharter"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                {{ __('guest.charter_title') }}
                            </h3>
                            <div class="mt-4">
                                <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto prose prose-sm max-w-none">
                                    {!! \App\Models\Setting::getCharterHtml() !!}
                                </div>
                                
                                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                {{ __('guest.charter_notice') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            @click="charterAccepted = true; showCharter = false; isSubmitting = true; $nextTick(() => { document.querySelector('form').submit(); })"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('guest.accept_charter') }}
                    </button>
                    <button type="button" 
                            @click="showCharter = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('guest.decline_charter') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection