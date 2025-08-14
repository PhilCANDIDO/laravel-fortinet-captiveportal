<div>
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Mon profil</h3>
            <p class="mt-1 text-sm text-gray-500">Gérer vos informations personnelles et paramètres de sécurité</p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button wire:click="$set('activeTab', 'profile')" class="{{ $activeTab === 'profile' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Informations
                </button>
                <button wire:click="$set('activeTab', 'password')" class="{{ $activeTab === 'password' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Mot de passe
                </button>
                <button wire:click="$set('activeTab', 'mfa')" class="{{ $activeTab === 'mfa' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Authentification à deux facteurs
                </button>
                <button wire:click="$set('activeTab', 'sessions')" class="{{ $activeTab === 'sessions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Sessions
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Profile Information -->
            @if($activeTab === 'profile')
                @if (session()->has('profile_message'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        {{ session('profile_message') }}
                    </div>
                @endif

                <form wire:submit="updateProfile" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input wire:model="name" type="text" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input wire:model="email" type="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rôle</label>
                        <p class="mt-1 text-sm text-gray-900">
                            @if(auth()->guard('admin')->user()->isSuperAdmin())
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                    Super Administrateur
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Administrateur
                                </span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dernière connexion</label>
                        <p class="mt-1 text-sm text-gray-900">
                            @if(auth()->guard('admin')->user()->last_login_at)
                                {{ auth()->guard('admin')->user()->last_login_at->format('d/m/Y H:i:s') }}
                                depuis {{ auth()->guard('admin')->user()->last_ip }}
                            @else
                                Jamais
                            @endif
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Enregistrer
                        </button>
                    </div>
                </form>
            @endif

            <!-- Password Change -->
            @if($activeTab === 'password')
                @if (session()->has('password_message'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        {{ session('password_message') }}
                    </div>
                @endif

                <form wire:submit="updatePassword" class="space-y-6">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Mot de passe actuel</label>
                        <input wire:model="current_password" type="password" id="current_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('current_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Nouveau mot de passe</label>
                        <input wire:model="password" type="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        <p class="mt-2 text-sm text-gray-500">
                            Le mot de passe doit contenir au moins 16 caractères, incluant majuscules, minuscules, chiffres et caractères spéciaux.
                        </p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmer le nouveau mot de passe</label>
                        <input wire:model="password_confirmation" type="password" id="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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
                                    Votre mot de passe expire 
                                    @if(auth()->guard('admin')->user()->password_expires_at)
                                        le {{ auth()->guard('admin')->user()->password_expires_at->format('d/m/Y') }}
                                    @else
                                        bientôt
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Changer le mot de passe
                        </button>
                    </div>
                </form>
            @endif

            <!-- MFA Settings -->
            @if($activeTab === 'mfa')
                @if (session()->has('mfa_message'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        {{ session('mfa_message') }}
                    </div>
                @endif

                @if (session()->has('mfa_error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        {{ session('mfa_error') }}
                    </div>
                @endif

                @if(!$mfaEnabled && !$showMfaSetup)
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Authentification à deux facteurs désactivée</h4>
                        <p class="text-sm text-gray-500 mb-4">
                            Protégez votre compte avec l'authentification à deux facteurs. Vous devrez entrer un code depuis votre application d'authentification en plus de votre mot de passe.
                        </p>
                        
                        @if(auth()->guard('admin')->user()->isInMfaGracePeriod())
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            Vous êtes en période de grâce. L'activation de l'authentification à deux facteurs sera obligatoire dans 
                                            {{ auth()->guard('admin')->user()->created_at->addDays(7)->diffForHumans() }}.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <button wire:click="initiateMfaSetup" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Activer l'authentification à deux facteurs
                        </button>
                    </div>
                @endif

                @if($showMfaSetup)
                    <div class="space-y-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-4">Configuration de l'authentification à deux facteurs</h4>
                            
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500 mb-2">1. Scannez ce code QR avec votre application d'authentification (Google Authenticator, Authy, etc.)</p>
                                    <div class="bg-white p-4 inline-block border rounded">
                                        {!! \App\Helpers\QrCode::size(200)->generate($mfaQrCode) !!}
                                    </div>
                                </div>
                                
                                <div>
                                    <p class="text-sm text-gray-500 mb-2">Ou entrez cette clé manuellement :</p>
                                    <code class="bg-gray-100 px-2 py-1 rounded text-sm">{{ $mfaSecret }}</code>
                                </div>
                                
                                <div>
                                    <p class="text-sm text-gray-500 mb-2">2. Entrez le code de vérification à 6 chiffres depuis votre application :</p>
                                    <input wire:model="verificationCode" type="text" maxlength="6" class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="000000">
                                    @error('verificationCode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-2">
                            <button wire:click="cancelMfaSetup" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Annuler
                            </button>
                            <button wire:click="verifyAndEnableMfa" type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Vérifier et activer
                            </button>
                        </div>
                    </div>
                @endif

                @if($mfaEnabled && !$showMfaSetup)
                    <div class="space-y-6">
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">Authentification à deux facteurs activée</h3>
                                    <p class="mt-1 text-sm text-green-700">
                                        Votre compte est protégé par l'authentification à deux facteurs.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Codes de récupération</h4>
                            <p class="text-sm text-gray-500 mb-4">
                                @if(auth()->guard('admin')->user()->getBackupCodesCount() > 0)
                                    Vous avez {{ auth()->guard('admin')->user()->getBackupCodesCount() }} codes de récupération restants.
                                @else
                                    Vous n'avez plus de codes de récupération.
                                @endif
                            </p>
                            <button wire:click="regenerateBackupCodes" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Régénérer les codes de récupération
                            </button>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Désactiver l'authentification à deux facteurs</h4>
                            <p class="text-sm text-gray-500 mb-4">
                                La désactivation de l'authentification à deux facteurs rendra votre compte moins sécurisé.
                            </p>
                            <button wire:click="disableMfa" onclick="return confirm('Êtes-vous sûr de vouloir désactiver l\'authentification à deux facteurs ?')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Désactiver l'authentification à deux facteurs
                            </button>
                        </div>
                    </div>
                @endif

                @if($showBackupCodes)
                    <div class="fixed z-10 inset-0 overflow-y-auto">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Codes de récupération</h3>
                                    
                                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-yellow-700">
                                                    Conservez ces codes dans un endroit sûr. Chaque code ne peut être utilisé qu'une seule fois.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-100 p-4 rounded">
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach($mfaBackupCodes as $code)
                                                <code class="text-sm">{{ $code }}</code>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button wire:click="downloadBackupCodes" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                        Télécharger
                                    </button>
                                    <button wire:click="hideBackupCodes" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Fermer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Active Sessions -->
            @if($activeTab === 'sessions')
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-4">Sessions actives</h4>
                        <p class="text-sm text-gray-500 mb-4">
                            Gérez et déconnectez vos sessions actives sur d'autres navigateurs et appareils.
                        </p>
                    </div>
                    
                    <div class="space-y-4">
                        @php
                            $sessions = auth()->guard('admin')->user()->sessions()->where('is_active', true)->orderBy('last_activity', 'desc')->get();
                        @endphp
                        
                        @forelse($sessions as $session)
                            <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        @if($session->id === session()->getId())
                                            Session actuelle
                                        @else
                                            Session
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        IP: {{ $session->ip_address }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $session->user_agent }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Dernière activité: {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                                    </p>
                                </div>
                                
                                @if($session->id !== session()->getId())
                                    <button wire:click="revokeSession('{{ $session->id }}')" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Révoquer
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Aucune session active trouvée.</p>
                        @endforelse
                    </div>
                    
                    @if($sessions->count() > 1)
                        <div>
                            <button wire:click="revokeAllSessions" onclick="return confirm('Êtes-vous sûr de vouloir révoquer toutes les autres sessions ?')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Révoquer toutes les autres sessions
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>