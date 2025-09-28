<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow">
        <h2 class="text-center text-2xl font-bold text-gray-900">{{ __('auth.login_title') }}</h2>
    
    <form wire:submit.prevent="login">
        <!-- Session Status -->
        @if (session()->has('warning'))
            <div class="mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded" role="alert" aria-live="polite">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm text-yellow-700">
                            {{ session('warning') }}
                        </p>
                        <button type="button" wire:click="openForceLogoutModal" class="mt-2 text-sm font-medium text-yellow-800 hover:text-yellow-900 underline">
                            {{ __('auth.force_logout_other_sessions') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('success'))
            <div class="mb-4 font-medium text-sm text-green-600" role="status" aria-live="polite">
                {{ session('success') }}
            </div>
        @endif

        <!-- Email Address -->
        <div>
            <label for="email" class="block font-medium text-sm text-gray-700">{{ __('auth.email') }}</label>
            <input wire:model="email" id="email" 
                   class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                   type="email" 
                   name="email" 
                   required 
                   autofocus 
                   autocomplete="username"
                   aria-label="{{ __('auth.email') }}"
                   aria-describedby="email-error"
                   aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" />
            @error('email')
                <span id="email-error" class="text-red-600 text-sm mt-1" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password" class="block font-medium text-sm text-gray-700">{{ __('auth.password') }}</label>
            <input wire:model="password" id="password" 
                   class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                   type="password" 
                   name="password" 
                   required 
                   autocomplete="current-password"
                   aria-label="{{ __('auth.password') }}"
                   aria-describedby="password-error"
                   aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" />
            @error('password')
                <span id="password-error" class="text-red-600 text-sm mt-1" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="remember" id="remember" 
                       type="checkbox" 
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" 
                       name="remember"
                       aria-label="{{ __('auth.remember_me') }}">
                <span class="ml-2 text-sm text-gray-600">{{ __('auth.remember_me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('admin.password.request') }}">
                {{ __('auth.forgot_password') }}
            </a>

            <button type="submit" 
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    aria-label="{{ __('auth.login_button') }}">
                <svg wire:loading wire:target="login" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="login">{{ __('auth.login_button') }}</span>
                <span wire:loading wire:target="login">{{ __('auth.processing') }}</span>
            </button>
        </div>
    </form>

    <!-- Force Logout Modal -->
    @if ($showForceLogoutModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('auth.force_logout_title') }}</h3>

                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-4">{{ __('auth.force_logout_description') }}</p>
                </div>

                <form wire:submit.prevent="forceLogout">
                    <div class="mb-4">
                        <label for="forceLogoutEmail" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.email') }}</label>
                        <input wire:model="forceLogoutEmail" id="forceLogoutEmail" type="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        @error('forceLogoutEmail')
                            <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="forceLogoutPassword" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.password') }}</label>
                        <input wire:model="forceLogoutPassword" id="forceLogoutPassword" type="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        @error('forceLogoutPassword')
                            <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showForceLogoutModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            {{ __('auth.force_logout_button') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    </div>
</div>