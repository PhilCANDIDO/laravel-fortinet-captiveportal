<div>
    <h2 class="text-center text-2xl font-bold text-gray-900 mb-6">Two-Factor Authentication</h2>
    
    <p class="text-sm text-gray-600 mb-4">
        Please enter your authentication code to continue. You can use either your authenticator app code or a backup code.
    </p>
    
    <form wire:submit.prevent="verify">
        <!-- Code -->
        <div>
            <label for="code" class="block font-medium text-sm text-gray-700">Authentication Code</label>
            <input wire:model="code" id="code" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" type="text" name="code" required autofocus autocomplete="one-time-code" placeholder="123456 or XXXX-XXXX" />
            @error('code')
                <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex items-center justify-end mt-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Verify
            </button>
        </div>
    </form>

    <div class="mt-4 text-center">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900 underline">
                Cancel and logout
            </button>
        </form>
    </div>
</div>