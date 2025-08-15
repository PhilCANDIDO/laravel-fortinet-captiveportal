<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @livewireStyles
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Language Selector -->
        <div class="absolute top-4 right-4" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false" type="button" 
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                @switch(app()->getLocale())
                    @case('fr')
                        <span class="fi fi-fr mr-2"></span>
                        Français
                        @break
                    @case('en')
                        <span class="fi fi-gb mr-2"></span>
                        English
                        @break
                    @case('it')
                        <span class="fi fi-it mr-2"></span>
                        Italiano
                        @break
                    @case('es')
                        <span class="fi fi-es mr-2"></span>
                        Español
                        @break
                @endswitch
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <!-- Dropdown menu -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="py-1">
                    @if(app()->getLocale() != 'fr')
                    <a href="/locale/fr?redirect={{ urlencode(request()->path()) }}" 
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <span class="fi fi-fr mr-3"></span>
                        Français
                    </a>
                    @endif
                    
                    @if(app()->getLocale() != 'en')
                    <a href="/locale/en?redirect={{ urlencode(request()->path()) }}" 
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <span class="fi fi-gb mr-3"></span>
                        English
                    </a>
                    @endif
                    
                    @if(app()->getLocale() != 'it')
                    <a href="/locale/it?redirect={{ urlencode(request()->path()) }}" 
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <span class="fi fi-it mr-3"></span>
                        Italiano
                    </a>
                    @endif
                    
                    @if(app()->getLocale() != 'es')
                    <a href="/locale/es?redirect={{ urlencode(request()->path()) }}" 
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <span class="fi fi-es mr-3"></span>
                        Español
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Page Content -->
        @yield('content')
    </div>

    @livewireScripts
</body>
</html>