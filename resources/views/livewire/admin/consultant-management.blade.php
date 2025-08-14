<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-900">{{ __('consultant.management_title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('consultant.management_subtitle') }}</p>
    </div>
    
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif
    
    <!-- Filters and Actions -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
            <x-search-input wire:model.live="search"
                           placeholder="{{ __('consultant.search_placeholder') }}" />
            
            <x-select wire:model.live="statusFilter">
                <option value="">{{ __('consultant.all_status') }}</option>
                <option value="active">{{ __('consultant.status_active') }}</option>
                <option value="suspended">{{ __('consultant.status_suspended') }}</option>
                <option value="expired">{{ __('consultant.status_expired') }}</option>
            </x-select>
        </div>
        
        <button wire:click="openCreateModal"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            {{ __('consultant.add_button') }}
        </button>
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th wire:click="sortBy('first_name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        <div class="flex items-center">
                            {{ __('consultant.name') }}
                            @if ($sortField === 'first_name')
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if ($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th wire:click="sortBy('email')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        <div class="flex items-center">
                            {{ __('consultant.email') }}
                            @if ($sortField === 'email')
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if ($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('consultant.company') }}
                    </th>
                    <th wire:click="sortBy('expires_at')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        <div class="flex items-center">
                            {{ __('consultant.expires_at') }}
                            @if ($sortField === 'expires_at')
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if ($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('consultant.status') }}
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('consultant.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($consultants as $consultant)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $consultant->name }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $consultant->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $consultant->company_name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ $consultant->expires_at->format('d/m/Y') }}
                                @if ($consultant->expires_at->isPast())
                                    <span class="ml-2 text-red-600">({{ __('consultant.expired') }})</span>
                                @elseif ($consultant->expires_at->diffInDays() <= 7)
                                    <span class="ml-2 text-yellow-600">({{ $consultant->expires_at->diffInDays() }} {{ __('consultant.days_remaining') }})</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($consultant->status === 'active')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ __('consultant.status_active') }}
                                </span>
                            @elseif ($consultant->status === 'suspended')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    {{ __('consultant.status_suspended') }}
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    {{ $consultant->status }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <button wire:click="edit({{ $consultant->id }})" 
                                        class="text-indigo-600 hover:text-indigo-900"
                                        title="{{ __('consultant.edit') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                
                                <button wire:click="extend({{ $consultant->id }})" 
                                        class="text-green-600 hover:text-green-900"
                                        title="{{ __('consultant.extend') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                
                                <button wire:click="resetPassword({{ $consultant->id }})" 
                                        class="text-yellow-600 hover:text-yellow-900"
                                        title="{{ __('consultant.reset_password') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                </button>
                                
                                <button wire:click="toggleStatus({{ $consultant->id }})" 
                                        class="{{ $consultant->status === 'active' ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' }}"
                                        title="{{ $consultant->status === 'active' ? __('consultant.suspend') : __('consultant.activate') }}">
                                    @if ($consultant->status === 'active')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                </button>
                                
                                <button wire:click="confirmDelete({{ $consultant->id }})" 
                                        class="text-red-600 hover:text-red-900"
                                        title="{{ __('consultant.delete') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            {{ __('consultant.no_consultants') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-4">
        {{ $consultants->links() }}
    </div>
    
    <!-- Create Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-screen overflow-y-auto">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('consultant.create_title') }}</h3>
                
                <form wire:submit="create">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.first_name') }}</label>
                            <input type="text" wire:model="first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.last_name') }}</label>
                            <input type="text" wire:model="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.email') }}</label>
                            <input type="email" wire:model="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.phone') }}</label>
                            <input type="text" wire:model="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.company') }}</label>
                            <input type="text" wire:model="company_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('company_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.expires_at') }}</label>
                            <input type="date" wire:model="expires_at" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('expires_at') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.notes') }}</label>
                            <textarea wire:model="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            @error('notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="send_credentials" class="mr-2">
                                <span class="text-sm text-gray-700">{{ __('consultant.send_credentials') }}</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            {{ __('common.create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    
    <!-- Edit Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-screen overflow-y-auto">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('consultant.edit_title') }}</h3>
                
                <form wire:submit="update">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.first_name') }}</label>
                            <input type="text" wire:model="first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.last_name') }}</label>
                            <input type="text" wire:model="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.email') }}</label>
                            <input type="email" wire:model="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.phone') }}</label>
                            <input type="text" wire:model="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.company') }}</label>
                            <input type="text" wire:model="company_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('company_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.expires_at') }}</label>
                            <input type="date" wire:model="expires_at" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" required>
                            @error('expires_at') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('consultant.notes') }}</label>
                            <textarea wire:model="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            @error('notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showEditModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            {{ __('common.update') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    
    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('consultant.delete_confirm_title') }}</h3>
                <p class="text-sm text-gray-500 mb-6">{{ __('consultant.delete_confirm_message') }}</p>
                
                <div class="flex justify-end space-x-3">
                    <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        {{ __('common.cancel') }}
                    </button>
                    <button wire:click="delete" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        {{ __('common.delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>