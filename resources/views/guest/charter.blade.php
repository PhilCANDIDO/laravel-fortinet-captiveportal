@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl w-full space-y-8">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-indigo-600">
                <h3 class="text-lg leading-6 font-medium text-white">
                    {{ __('charter.title') }}
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-indigo-200">
                    {{ __('charter.subtitle') }}
                </p>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
                <div class="prose prose-sm max-w-none mb-6" style="max-height: 400px; overflow-y: auto;">
                    {!! nl2br(e($charter)) !!}
                </div>
                
                <form action="{{ route('guest.charter.accept') }}" method="POST">
                    @csrf
                    
                    <div class="mb-6">
                        <label class="flex items-start">
                            <input type="checkbox" 
                                   name="accept" 
                                   id="accept" 
                                   value="1"
                                   required
                                   class="mt-0.5 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">
                                {{ __('charter.accept_text') }}
                            </span>
                        </label>
                        @error('accept')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <a href="{{ route('guest.register') }}" 
                           class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('charter.decline_button') }}
                        </a>
                        
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('charter.accept_button') }}
                            <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection