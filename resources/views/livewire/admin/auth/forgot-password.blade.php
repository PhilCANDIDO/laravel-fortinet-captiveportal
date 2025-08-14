<div>
    <h2 class="text-center text-2xl font-bold text-gray-900 mb-6">Reset Password</h2>
    
    <p class="text-sm text-gray-600 mb-4">
        Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.
    </p>
    
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif
    
    <form wire:submit.prevent="sendResetLink">
        <!-- Email Address -->
        <div>
            <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
            <input wire:model="email" id="email" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" type="email" name="email" required autofocus />
            @error('email')
                <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="text-sm text-gray-600 hover:text-gray-900" href="{{ route('admin.login') }}">
                Back to login
            </a>
            
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Email Password Reset Link
            </button>
        </div>
    </form>
</div>