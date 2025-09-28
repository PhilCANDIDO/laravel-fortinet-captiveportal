<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('admin.audit_logs') }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ __('admin.audit_logs_description') }}</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.search') }}</label>
                    <input wire:model.live.debounce.300ms="search" type="text" id="search"
                           placeholder="{{ __('admin.search_audit_logs') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Event Type Filter -->
                <div>
                    <label for="eventTypeFilter" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.event_type') }}</label>
                    <select wire:model.live="eventTypeFilter" id="eventTypeFilter"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach($eventTypes as $key => $value)
                            <option value="{{ $value }}">{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Category Filter -->
                <div>
                    <label for="eventCategoryFilter" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.category') }}</label>
                    <select wire:model.live="eventCategoryFilter" id="eventCategoryFilter"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach($eventCategories as $key => $value)
                            <option value="{{ $value }}">{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.status') }}</label>
                    <select wire:model.live="statusFilter" id="statusFilter"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach($statuses as $key => $value)
                            <option value="{{ $value }}">{{ ucfirst($value) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label for="startDate" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.start_date') }}</label>
                    <input wire:model.live="startDate" type="date" id="startDate"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- End Date -->
                <div>
                    <label for="endDate" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.end_date') }}</label>
                    <input wire:model.live="endDate" type="date" id="endDate"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- IP Address Filter -->
                <div>
                    <label for="ipAddressFilter" class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.ip_address') }}</label>
                    <input wire:model.live.debounce.300ms="ipAddressFilter" type="text" id="ipAddressFilter"
                           placeholder="192.168.1.1"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Per Page -->
                <div>
                    <label for="perPage" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.per_page') }}</label>
                    <select wire:model.live="perPage" id="perPage"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center">
                <button wire:click="resetFilters" type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ __('common.reset_filters') }}
                </button>

                <button wire:click="exportToExcel" type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('common.export_excel') }}
                </button>
            </div>
        </div>

        <!-- Results count -->
        <div class="mb-4 text-sm text-gray-600">
            {{ __('common.showing') }} {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} {{ __('common.of') }} {{ $logs->total() }} {{ __('common.results') }}
        </div>

        <!-- Table -->
        <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('common.date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.event_type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.category') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.user') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.ip_address') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.action') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ ucfirst(str_replace('_', ' ', $log->event_type)) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($log->event_category === 'authentication') bg-blue-100 text-blue-800
                                    @elseif($log->event_category === 'authorization') bg-purple-100 text-purple-800
                                    @elseif($log->event_category === 'user_management') bg-yellow-100 text-yellow-800
                                    @elseif($log->event_category === 'system') bg-gray-100 text-gray-800
                                    @else bg-indigo-100 text-indigo-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $log->event_category)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->user_email ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ $log->ip_address ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->action ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($log->status === 'success') bg-green-100 text-green-800
                                    @elseif($log->status === 'failure') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="viewDetails({{ $log->id }})" class="text-indigo-600 hover:text-indigo-900">
                                    {{ __('common.view_details') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
                                {{ __('common.no_results') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedLog)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showDetailModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                    {{ __('admin.audit_log_details') }}
                                </h3>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('common.date') }}</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->created_at->format('Y-m-d H:i:s') }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.event_type') }}</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $selectedLog->event_type)) }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.category') }}</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $selectedLog->event_category)) }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.status') }}</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ ucfirst($selectedLog->status) }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.user_type') }}</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->user_type ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.user_id') }}</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->user_id ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.user_email') }}</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->user_email ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.ip_address') }}</label>
                                            <p class="mt-1 text-sm text-gray-900 font-mono">{{ $selectedLog->ip_address ?? '-' }}</p>
                                        </div>
                                    </div>

                                    @if($selectedLog->action)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.action') }}</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->action }}</p>
                                        </div>
                                    @endif

                                    @if($selectedLog->resource_type)
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">{{ __('admin.resource_type') }}</label>
                                                <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->resource_type }}</p>
                                            </div>
                                            @if($selectedLog->resource_id)
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">{{ __('admin.resource_id') }}</label>
                                                    <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->resource_id }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if($selectedLog->message)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('common.message') }}</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLog->message }}</p>
                                        </div>
                                    @endif

                                    @if($selectedLog->user_agent)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.user_agent') }}</label>
                                            <p class="mt-1 text-sm text-gray-900 break-all">{{ $selectedLog->user_agent }}</p>
                                        </div>
                                    @endif

                                    @if($selectedLog->metadata)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.metadata') }}</label>
                                            <pre class="mt-1 text-xs text-gray-900 bg-gray-50 p-3 rounded overflow-x-auto">{{ json_encode($selectedLog->metadata, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    @endif

                                    @if($selectedLog->old_values)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.old_values') }}</label>
                                            <pre class="mt-1 text-xs text-gray-900 bg-gray-50 p-3 rounded overflow-x-auto">{{ json_encode($selectedLog->old_values, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    @endif

                                    @if($selectedLog->new_values)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.new_values') }}</label>
                                            <pre class="mt-1 text-xs text-gray-900 bg-gray-50 p-3 rounded overflow-x-auto">{{ json_encode($selectedLog->new_values, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">{{ __('admin.session_id') }}</label>
                                        <p class="mt-1 text-xs text-gray-900 font-mono">{{ $selectedLog->session_id ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="$set('showDetailModal', false)" type="button"
                                class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                            {{ __('common.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>