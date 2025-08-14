<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @auth('admin')
    <script>
        // Set session timeout from config (must be set before app.js loads)
        window.sessionTimeout = {{ config('session.lifetime', 15) * 60 * 1000 }};
        window.translations = {
            session_warning_title: "{{ __('auth.session_warning') }}",
            session_warning_message: "{{ __('auth.session_warning_message') }}",
            extend_session: "{{ __('auth.extend_session') }}",
            logout: "{{ __('auth.logout') }}"
        };
    </script>
    @endauth
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 bg-indigo-600 text-white p-2 rounded-md">
        Skip to main content
    </a>
    
    <div class="min-h-screen bg-gray-100">
        @auth('admin')
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-100" role="navigation" aria-label="Main navigation">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('admin.dashboard') }}" class="text-xl font-semibold">
                                Admin Panel
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex items-stretch" role="menubar">
                            <a href="{{ route('admin.dashboard') }}" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.dashboard') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium"
                               role="menuitem"
                               aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}">
                                Dashboard
                            </a>
                            <!-- Users Dropdown -->
                            <div class="relative flex items-stretch" x-data="{ open: false }">
                                <button @click="open = !open" 
                                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.users.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                    Users
                                    <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div x-show="open" 
                                     @click.away="open = false"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 transform scale-100"
                                     x-transition:leave-end="opacity-0 transform scale-95"
                                     class="absolute top-full left-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                                     style="display: none;">
                                    <div class="py-1">
                                        <a href="{{ route('admin.users.guests') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Invités
                                        </a>
                                        <a href="{{ route('admin.users.consultants') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Consultants
                                        </a>
                                        <a href="{{ route('admin.users.employees') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Employés
                                        </a>
                                        <a href="{{ route('admin.users.admins') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Administrateurs
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('admin.audit.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.audit.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                Audit Logs
                            </a>
                            <!-- Settings Dropdown -->
                            <div class="relative flex items-stretch" x-data="{ open: false }">
                                <button @click="open = !open" 
                                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.settings.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                    Settings
                                    <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div x-show="open" 
                                     @click.away="open = false"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 transform scale-100"
                                     x-transition:leave-end="opacity-0 transform scale-95"
                                     class="absolute top-full left-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                                     style="display: none;">
                                    <div class="py-1">
                                        <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            General Settings
                                        </a>
                                        <a href="{{ route('admin.settings.fortigate') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            FortiGate Configuration
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-4">
                        <!-- Session Indicator -->
                        <div id="session-indicator" 
                             class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"
                             role="status"
                             aria-live="polite"
                             aria-label="Session status">
                            <svg class="w-2 h-2 mr-1 fill-current" viewBox="0 0 8 8" aria-hidden="true">
                                <circle cx="4" cy="4" r="3"/>
                            </svg>
                            <span>Session active</span>
                        </div>
                        
                        <!-- Language Selector -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                                </svg>
                                {{ strtoupper(app()->getLocale()) }}
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-32 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                                <div class="py-1">
                                    <a href="?locale=fr" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ app()->getLocale() == 'fr' ? 'bg-gray-50' : '' }}">Français</a>
                                    <a href="?locale=en" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ app()->getLocale() == 'en' ? 'bg-gray-50' : '' }}">English</a>
                                    <a href="?locale=it" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ app()->getLocale() == 'it' ? 'bg-gray-50' : '' }}">Italiano</a>
                                    <a href="?locale=es" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ app()->getLocale() == 'es' ? 'bg-gray-50' : '' }}">Español</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition duration-150 ease-in-out">
                                <span>{{ auth('admin')->user()->name }}</span>
                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                                <div class="py-1">
                                    <a href="{{ route('admin.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                    <a href="{{ route('admin.mfa.setup') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">MFA Settings</a>
                                    <hr class="my-1">
                                    <form method="POST" action="{{ route('admin.logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        @endauth

        <!-- Page Content -->
        <main id="main-content" role="main" aria-label="Main content">
            @yield('content', $slot ?? '')
        </main>
    </div>

    <!-- Notification Toast -->
    <div x-data="{ notifications: [] }" 
         @notify.window="
            let notification = $event.detail;
            notifications.push(notification);
            setTimeout(() => {
                notifications = notifications.filter(n => n !== notification);
            }, 5000);
         "
         class="fixed bottom-0 right-0 z-50 p-6 space-y-4">
        <template x-for="notification in notifications" :key="notification">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-full"
                 class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <template x-if="notification.type === 'success'">
                                <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </template>
                            <template x-if="notification.type === 'error'">
                                <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </template>
                            <template x-if="notification.type === 'info'">
                                <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </template>
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900" x-text="notification.message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @livewireScripts
</body>
</html>