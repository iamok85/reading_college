<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-6 text-sm font-semibold text-gray-700">
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
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-gray-900">Credits Usage</h2>
                <p class="mt-2 text-sm text-gray-600">Track how credits are used across features.</p>
                <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                    Current balance: <span class="font-semibold">{{ auth()->user()->credits ?? 0 }}</span>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="text-sm font-semibold text-gray-800">Costs</h3>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    <li>Essay correction + analysis: 5 credits</li>
                    <li>Image generation: 10 credits</li>
                    <li>Video generation: 20 credits</li>
                    <li>Reading recommendations: 20 credits</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
