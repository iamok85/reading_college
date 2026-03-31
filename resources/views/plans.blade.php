<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-6 text-sm font-semibold text-gray-700">
            <a href="{{ route('feeds') }}" class="hover:text-gray-900 {{ request()->routeIs('feeds') ? 'text-gray-900 underline' : '' }}">
                {{ __('Feeds') }}
            </a>
            @auth
                <a href="{{ route('dashboard') }}" class="hover:text-gray-900 {{ request()->routeIs('dashboard') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Dashboard') }}
                </a>
                <a href="{{ route('jobs') }}" class="hover:text-gray-900 {{ request()->routeIs('jobs') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Processing') }}
                </a>
                <a href="{{ route('previous-essays') }}" class="hover:text-gray-900 {{ request()->routeIs('previous-essays') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Previous Essays') }}
                </a>
                <a href="{{ route('reading-recommendations') }}" class="hover:text-gray-900 {{ request()->routeIs('reading-recommendations') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Readings') }}
                </a>
                <a href="{{ route('analysis') }}" class="hover:text-gray-900 {{ request()->routeIs('analysis') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Analysis') }}
                </a>
            @else
                <a href="{{ route('about') }}" class="hover:text-gray-900 {{ request()->routeIs('about') ? 'text-gray-900 underline' : '' }}">
                    {{ __('About') }}
                </a>
                <a href="{{ route('contact') }}" class="hover:text-gray-900 {{ request()->routeIs('contact') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Contact') }}
                </a>
                <a href="{{ route('demo') }}" class="hover:text-gray-900 {{ request()->routeIs('demo') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Demo') }}
                </a>
                <a href="{{ route('plans') }}" class="hover:text-gray-900 {{ request()->routeIs('plans') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Plans') }}
                </a>
                <div class="ml-auto flex items-center gap-4 text-sm font-medium text-gray-600">
                    <a class="hover:text-gray-900" href="{{ route('login') }}">Sign in</a>
                    <a class="rounded-md bg-gray-900 px-3 py-1.5 text-white hover:bg-gray-800" href="{{ route('register') }}">Register</a>
                </div>
            @endauth
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-gray-900">Plans</h2>
                <p class="mt-2 text-sm text-gray-600">All plans include the first month free, then billed monthly.</p>
                <div class="mt-6 flex flex-wrap items-stretch justify-center gap-4">
                    <div class="w-full rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm sm:w-48">
                        <div class="text-sm font-semibold text-gray-700">Silver</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">$10</div>
                        <div class="text-xs text-gray-500">20 submissions / month</div>
                    </div>
                    <div class="w-full rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-left shadow-sm sm:w-48">
                        <div class="text-sm font-semibold text-indigo-700">Gold</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">$20</div>
                        <div class="text-xs text-gray-600">50 submissions / month</div>
                    </div>
                    <div class="w-full rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm sm:w-48">
                        <div class="text-sm font-semibold text-gray-700">Premium</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900">$30</div>
                        <div class="text-xs text-gray-500">Unlimited submissions</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
