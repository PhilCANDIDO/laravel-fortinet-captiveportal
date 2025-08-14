<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ __('auth.password.change') }}
            </h2>
            
            @if(session('must_change_password'))
                <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                {{ __('auth.first_login_password_change') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <form wire:submit="changePassword" class="mt-8 space-y-6">
            <div class="space-y-4">
                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">
                        {{ __('auth.password.current') }}
                    </label>
                    <input wire:model="current_password" type="password" id="current_password" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           required>
                    @error('current_password')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        {{ __('auth.password.new') }}
                    </label>
                    <input wire:model.live="password" type="password" id="password" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           required aria-describedby="password-strength">
                    
                    <!-- Password Strength Indicator -->
                    @if($password)
                        <div class="mt-2">
                            <div class="flex items-center space-x-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all duration-300
                                        @if($passwordStrength >= 80) bg-green-500
                                        @elseif($passwordStrength >= 60) bg-yellow-500
                                        @elseif($passwordStrength >= 40) bg-orange-500
                                        @else bg-red-500
                                        @endif"
                                        style="width: {{ $passwordStrength }}%">
                                    </div>
                                </div>
                                <span class="text-xs font-medium
                                    @if($passwordStrength >= 80) text-green-600
                                    @elseif($passwordStrength >= 60) text-yellow-600
                                    @elseif($passwordStrength >= 40) text-orange-600
                                    @else text-red-600
                                    @endif">
                                    @if($passwordStrength >= 80) {{ __('auth.password_strength.very_strong') }}
                                    @elseif($passwordStrength >= 60) {{ __('auth.password_strength.strong') }}
                                    @elseif($passwordStrength >= 40) {{ __('auth.password_strength.good') }}
                                    @elseif($passwordStrength >= 20) {{ __('auth.password_strength.fair') }}
                                    @else {{ __('auth.password_strength.weak') }}
                                    @endif
                                </span>
                            </div>
                            @if($passwordFeedback)
                                <p class="text-xs text-gray-600 mt-1">{{ $passwordFeedback }}</p>
                            @endif
                        </div>
                    @endif
                    
                    @error('password')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                    
                    <p class="mt-2 text-xs text-gray-500">
                        {{ __('auth.password.requirements') }}
                    </p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        {{ __('auth.password.confirm') }}
                    </label>
                    <input wire:model="password_confirmation" type="password" id="password_confirmation" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           required>
                </div>
            </div>

            <div>
                <button type="submit" 
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span wire:loading.remove>{{ __('auth.password.change') }}</span>
                    <span wire:loading>{{ __('auth.processing') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>