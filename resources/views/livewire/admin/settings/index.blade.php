<div>
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Paramètres du système</h3>
            <p class="mt-1 text-sm text-gray-500">Gérer les paramètres de l'application et de la sécurité</p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button wire:click="$set('activeTab', 'general')" class="{{ $activeTab === 'general' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Général
                </button>
                <button wire:click="$set('activeTab', 'security')" class="{{ $activeTab === 'security' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Sécurité
                </button>
                <button wire:click="$set('activeTab', 'charter')" class="{{ $activeTab === 'charter' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Charte
                </button>
                <button wire:click="$set('activeTab', 'email')" class="{{ $activeTab === 'email' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Email
                </button>
                <button wire:click="$set('activeTab', 'users')" class="{{ $activeTab === 'users' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Utilisateurs
                </button>
                <button wire:click="$set('activeTab', 'maintenance')" class="{{ $activeTab === 'maintenance' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Maintenance
                </button>
            </nav>
        </div>

        <div class="p-6">
            @if (session()->has('message'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('message') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <!-- General Settings -->
            @if($activeTab === 'general')
                <form wire:submit="saveGeneralSettings" class="space-y-6">
                    <div>
                        <label for="site_name" class="block text-sm font-medium text-gray-700">Nom du site</label>
                        <input wire:model="site_name" type="text" id="site_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('site_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="site_description" class="block text-sm font-medium text-gray-700">Description du site</label>
                        <textarea wire:model="site_description" id="site_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        @error('site_description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700">Email de contact</label>
                        <input wire:model="contact_email" type="email" id="contact_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('contact_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Enregistrer
                        </button>
                    </div>
                </form>
            @endif

            <!-- Security Settings -->
            @if($activeTab === 'security')
                <form wire:submit="saveSecuritySettings" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="audit_retention_days" class="block text-sm font-medium text-gray-700">Rétention des logs d'audit (jours)</label>
                            <input wire:model="audit_retention_days" type="number" id="audit_retention_days" min="30" max="3650" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('audit_retention_days') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="session_timeout_minutes" class="block text-sm font-medium text-gray-700">Timeout de session (minutes)</label>
                            <input wire:model="session_timeout_minutes" type="number" id="session_timeout_minutes" min="5" max="120" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('session_timeout_minutes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="max_login_attempts" class="block text-sm font-medium text-gray-700">Tentatives de connexion max</label>
                            <input wire:model="max_login_attempts" type="number" id="max_login_attempts" min="3" max="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('max_login_attempts') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="lockout_duration_minutes" class="block text-sm font-medium text-gray-700">Durée de blocage (minutes)</label>
                            <input wire:model="lockout_duration_minutes" type="number" id="lockout_duration_minutes" min="5" max="1440" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('lockout_duration_minutes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="password_expiry_days" class="block text-sm font-medium text-gray-700">Expiration mot de passe (jours)</label>
                            <input wire:model="password_expiry_days" type="number" id="password_expiry_days" min="30" max="365" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('password_expiry_days') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="mfa_grace_period_days" class="block text-sm font-medium text-gray-700">Période de grâce MFA (jours)</label>
                            <input wire:model="mfa_grace_period_days" type="number" id="mfa_grace_period_days" min="0" max="30" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('mfa_grace_period_days') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Enregistrer
                        </button>
                    </div>
                </form>
            @endif

            <!-- Charter Settings -->
            @if($activeTab === 'charter')
                <div class="space-y-6">
                    <!-- Markdown Help -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-blue-900 mb-2">Formatage Markdown</h4>
                        <div class="text-xs text-blue-700 grid grid-cols-2 gap-4">
                            <div>
                                <p class="mb-1"><code class="bg-blue-100 px-1"># Titre 1</code> → Titre principal</p>
                                <p class="mb-1"><code class="bg-blue-100 px-1">## Titre 2</code> → Sous-titre</p>
                                <p class="mb-1"><code class="bg-blue-100 px-1">**texte**</code> → <strong>texte en gras</strong></p>
                                <p class="mb-1"><code class="bg-blue-100 px-1">*texte*</code> → <em>texte en italique</em></p>
                            </div>
                            <div>
                                <p class="mb-1"><code class="bg-blue-100 px-1">- élément</code> → Liste à puces</p>
                                <p class="mb-1"><code class="bg-blue-100 px-1">1. élément</code> → Liste numérotée</p>
                                <p class="mb-1"><code class="bg-blue-100 px-1">> citation</code> → Citation</p>
                                <p class="mb-1"><code class="bg-blue-100 px-1">---</code> → Ligne horizontale</p>
                            </div>
                        </div>
                    </div>
                    
                    <form wire:submit="saveCharterSettings" class="space-y-6">
                        <!-- French Charter -->
                        <div x-data="{ showPreview: false }">
                            <div class="flex justify-between items-center mb-2">
                                <label for="charter_text_fr" class="block text-sm font-medium text-gray-700">Charte d'utilisation (Français)</label>
                                <button type="button" @click="showPreview = !showPreview" class="text-sm text-blue-600 hover:text-blue-800">
                                    <span x-text="showPreview ? 'Éditer' : 'Aperçu'"></span>
                                </button>
                            </div>
                            <div x-show="!showPreview">
                                <textarea wire:model="charter_text_fr" id="charter_text_fr" rows="10" 
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                                          placeholder="# Titre de la charte&#10;&#10;## Section 1&#10;Contenu en **markdown**..."></textarea>
                                @error('charter_text_fr') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div x-show="showPreview" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 p-4 prose prose-sm max-w-none max-h-64 overflow-y-auto">
                                {!! app(\App\Services\MarkdownService::class)->parseWithStyles($charter_text_fr) !!}
                            </div>
                        </div>

                        <!-- English Charter -->
                        <div x-data="{ showPreview: false }">
                            <div class="flex justify-between items-center mb-2">
                                <label for="charter_text_en" class="block text-sm font-medium text-gray-700">Terms of Use (English)</label>
                                <button type="button" @click="showPreview = !showPreview" class="text-sm text-blue-600 hover:text-blue-800">
                                    <span x-text="showPreview ? 'Edit' : 'Preview'"></span>
                                </button>
                            </div>
                            <div x-show="!showPreview">
                                <textarea wire:model="charter_text_en" id="charter_text_en" rows="10" 
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                                          placeholder="# Charter Title&#10;&#10;## Section 1&#10;Content in **markdown**..."></textarea>
                                @error('charter_text_en') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div x-show="showPreview" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 p-4 prose prose-sm max-w-none max-h-64 overflow-y-auto">
                                {!! app(\App\Services\MarkdownService::class)->parseWithStyles($charter_text_en) !!}
                            </div>
                        </div>

                        <!-- Italian Charter -->
                        <div x-data="{ showPreview: false }">
                            <div class="flex justify-between items-center mb-2">
                                <label for="charter_text_it" class="block text-sm font-medium text-gray-700">Termini di utilizzo (Italiano)</label>
                                <button type="button" @click="showPreview = !showPreview" class="text-sm text-blue-600 hover:text-blue-800">
                                    <span x-text="showPreview ? 'Modifica' : 'Anteprima'"></span>
                                </button>
                            </div>
                            <div x-show="!showPreview">
                                <textarea wire:model="charter_text_it" id="charter_text_it" rows="10" 
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                                          placeholder="# Titolo della carta&#10;&#10;## Sezione 1&#10;Contenuto in **markdown**..."></textarea>
                                @error('charter_text_it') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div x-show="showPreview" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 p-4 prose prose-sm max-w-none max-h-64 overflow-y-auto">
                                {!! app(\App\Services\MarkdownService::class)->parseWithStyles($charter_text_it) !!}
                            </div>
                        </div>

                        <!-- Spanish Charter -->
                        <div x-data="{ showPreview: false }">
                            <div class="flex justify-between items-center mb-2">
                                <label for="charter_text_es" class="block text-sm font-medium text-gray-700">Términos de uso (Español)</label>
                                <button type="button" @click="showPreview = !showPreview" class="text-sm text-blue-600 hover:text-blue-800">
                                    <span x-text="showPreview ? 'Editar' : 'Vista previa'"></span>
                                </button>
                            </div>
                            <div x-show="!showPreview">
                                <textarea wire:model="charter_text_es" id="charter_text_es" rows="10" 
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                                          placeholder="# Título de la carta&#10;&#10;## Sección 1&#10;Contenido en **markdown**..."></textarea>
                                @error('charter_text_es') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div x-show="showPreview" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 p-4 prose prose-sm max-w-none max-h-64 overflow-y-auto">
                                {!! app(\App\Services\MarkdownService::class)->parseWithStyles($charter_text_es) !!}
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Email Settings -->
            @if($activeTab === 'email')
                <form wire:submit="saveEmailSettings" class="space-y-6">
                    <div>
                        <label for="email_from_name" class="block text-sm font-medium text-gray-700">Nom de l'expéditeur</label>
                        <input wire:model="email_from_name" type="text" id="email_from_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('email_from_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="email_from_address" class="block text-sm font-medium text-gray-700">Adresse email de l'expéditeur</label>
                        <input wire:model="email_from_address" type="email" id="email_from_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('email_from_address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Enregistrer
                        </button>
                    </div>
                </form>
            @endif

            <!-- User Settings -->
            @if($activeTab === 'users')
                <form wire:submit="saveUserSettings" class="space-y-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-4">Paramètres des invités</h4>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="guest_access_duration_hours" class="block text-sm font-medium text-gray-700">Durée d'accès (heures)</label>
                                <input wire:model="guest_access_duration_hours" type="number" id="guest_access_duration_hours" min="1" max="168" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('guest_access_duration_hours') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="guest_validation_timeout_minutes" class="block text-sm font-medium text-gray-700">Délai de validation (minutes)</label>
                                <input wire:model="guest_validation_timeout_minutes" type="number" id="guest_validation_timeout_minutes" min="5" max="120" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('guest_validation_timeout_minutes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-4">Paramètres des consultants</h4>
                        <div>
                            <label for="consultant_max_duration_days" class="block text-sm font-medium text-gray-700">Durée maximale (jours)</label>
                            <input wire:model="consultant_max_duration_days" type="number" id="consultant_max_duration_days" min="1" max="730" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('consultant_max_duration_days') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Enregistrer
                        </button>
                    </div>
                </form>
            @endif

            <!-- Maintenance -->
            @if($activeTab === 'maintenance')
                <div class="space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-4">Actions de maintenance</h4>
                        
                        <div class="space-y-4">
                            @if(auth()->guard('admin')->user()->isSuperAdmin())
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Exporter les logs d'audit</p>
                                        <p class="text-sm text-gray-500">Télécharger tous les logs d'audit au format Excel</p>
                                    </div>
                                    <button wire:click="exportAuditLogs" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Exporter
                                    </button>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Vider le cache</p>
                                        <p class="text-sm text-gray-500">Vider tous les caches de l'application</p>
                                    </div>
                                    <button wire:click="clearCache" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Vider le cache
                                    </button>
                                </div>
                            @else
                                <p class="text-sm text-gray-500">Seuls les super administrateurs peuvent effectuer des actions de maintenance.</p>
                            @endif
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Les actions de maintenance peuvent affecter les performances de l'application. Utilisez-les avec précaution.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>