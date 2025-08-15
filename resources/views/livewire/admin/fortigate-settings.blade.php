<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-6">Configuration FortiGate</h2>
            
            @if (session()->has('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            
            @if (session()->has('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif
            
            <form wire:submit.prevent="save">
                <!-- Service Status -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">État du Service</h3>
                            <p class="text-sm text-gray-600">
                                @if($is_active)
                                    <span class="text-green-600">● Service actif</span>
                                @else
                                    <span class="text-red-600">● Service inactif</span>
                                @endif
                            </p>
                            @if($settings->last_connection_test)
                                <p class="text-xs text-gray-500 mt-1">
                                    Dernier test: {{ $settings->last_connection_test->format('d/m/Y H:i:s') }}
                                    @if($settings->last_connection_status)
                                        <span class="text-green-600">✓</span>
                                    @else
                                        <span class="text-red-600">✗</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" 
                                    wire:click="toggleService"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                {{ $is_active ? 'Désactiver' : 'Activer' }}
                            </button>
                            <button type="button" 
                                    wire:click="testConnection"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="testConnection">Tester la connexion</span>
                                <span wire:loading wire:target="testConnection">Test en cours...</span>
                            </button>
                        </div>
                    </div>
                    
                    @if($connectionTestResult)
                        <div class="mt-4 p-3 rounded {{ $connectionTestResult['success'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            <p class="font-semibold">{{ $connectionTestResult['message'] }}</p>
                            <p class="text-sm">{{ $connectionTestResult['details'] }}</p>
                        </div>
                    @endif
                </div>
                
                <!-- Connection Settings -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Paramètres de connexion</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">URL de l'API FortiGate</label>
                            <input type="url" wire:model="api_url" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('api_url') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Token API</label>
                            <input type="password" wire:model="api_token" 
                                   placeholder="Laisser vide pour conserver l'actuel"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('api_token') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Délai d'attente (secondes)</label>
                            <input type="number" wire:model="timeout" min="5" max="300"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('timeout') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" wire:model="verify_ssl" 
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">Vérifier le certificat SSL</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- User Management -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Gestion des utilisateurs</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Groupe utilisateurs FortiGate</label>
                            <input type="text" wire:model="user_group" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('user_group') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">URL du portail captif</label>
                            <input type="url" wire:model="captive_portal_url" 
                                   placeholder="https://192.168.1.1:1003/fgtauth"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('captive_portal_url') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Longueur mot de passe par défaut</label>
                            <input type="number" wire:model="default_password_length" min="8" max="32"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('default_password_length') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                
                <!-- Session Timeouts -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Durées de session (secondes)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Session standard</label>
                            <input type="number" wire:model="session_timeout" min="60"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('session_timeout') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Session invité</label>
                            <input type="number" wire:model="guest_session_timeout" min="60"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('guest_session_timeout') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Session consultant</label>
                            <input type="number" wire:model="consultant_session_timeout" min="60"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('consultant_session_timeout') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Settings (Collapsible) -->
                <details class="mb-6">
                    <summary class="cursor-pointer text-lg font-semibold mb-4">Paramètres avancés</summary>
                    
                    <!-- Retry Configuration -->
                    <div class="mb-6 mt-4">
                        <h4 class="text-md font-semibold mb-3">Configuration des tentatives</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nombre max de tentatives</label>
                                <input type="number" wire:model="retry_max_attempts" min="1" max="10"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('retry_max_attempts') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Délai initial (ms)</label>
                                <input type="number" wire:model="retry_initial_delay" min="100" max="10000"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('retry_initial_delay') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Délai max (ms)</label>
                                <input type="number" wire:model="retry_max_delay" min="1000" max="60000"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('retry_max_delay') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Multiplicateur</label>
                                <input type="number" wire:model="retry_multiplier" min="1" max="5" step="0.1"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('retry_multiplier') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Circuit Breaker -->
                    <div class="mb-6">
                        <h4 class="text-md font-semibold mb-3">Circuit Breaker</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Seuil d'échec</label>
                                <input type="number" wire:model="circuit_breaker_failure_threshold" min="1" max="20"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('circuit_breaker_failure_threshold') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Temps de récupération (s)</label>
                                <input type="number" wire:model="circuit_breaker_recovery_time" min="10" max="600"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('circuit_breaker_recovery_time') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Seuil de succès</label>
                                <input type="number" wire:model="circuit_breaker_success_threshold" min="1" max="10"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('circuit_breaker_success_threshold') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cache & Logging -->
                    <div class="mb-6">
                        <h4 class="text-md font-semibold mb-3">Cache et journalisation</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" wire:model="cache_enabled" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="text-sm font-medium text-gray-700">Activer le cache</span>
                                </label>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Durée du cache (s)</label>
                                <input type="number" wire:model="cache_ttl" min="0" max="3600"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('cache_ttl') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" wire:model="logging_enabled" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="text-sm font-medium text-gray-700">Activer la journalisation</span>
                                </label>
                            </div>
                            
                            <div>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" wire:model="log_requests" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="text-sm font-medium text-gray-700">Journaliser les requêtes</span>
                                </label>
                            </div>
                            
                            <div>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" wire:model="log_responses" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="text-sm font-medium text-gray-700">Journaliser les réponses</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </details>
                
                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Enregistrer les paramètres
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>