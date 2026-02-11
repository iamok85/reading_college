<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-6 text-sm font-semibold text-gray-700">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900 {{ request()->routeIs('dashboard') ? 'text-gray-900 underline' : '' }}">
                {{ __('Dashboard') }}
            </a>
            <a href="{{ route('previous-essays') }}" class="hover:text-gray-900 {{ request()->routeIs('previous-essays') ? 'text-gray-900 underline' : '' }}">
                {{ __('Previous Essays') }}
            </a>
            <a href="{{ route('reading-recommendations') }}" class="hover:text-gray-900 {{ request()->routeIs('reading-recommendations') ? 'text-gray-900 underline' : '' }}">
                {{ __('Reading Recommendations') }}
            </a>
            <a href="{{ route('billing') }}" class="hover:text-gray-900 {{ request()->routeIs('billing') ? 'text-gray-900 underline' : '' }}">
                {{ __('Billing') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-8 shadow-sm">
                <div class="text-center">
                    <h1 class="text-2xl font-semibold text-gray-900">Choose your plan</h1>
                    <p class="mt-2 text-sm text-gray-600">Upgrade anytime. Plans are billed monthly.</p>
                </div>

                <div class="mt-8 flex flex-nowrap items-stretch justify-center gap-6 overflow-x-auto pb-2">
                    <div class="w-64 shrink-0 rounded-lg border border-gray-200 p-6">
                        <div class="text-sm font-semibold text-gray-700">Silver</div>
                        <div class="mt-3 flex items-end gap-2">
                            <span class="text-3xl font-semibold text-gray-900">$10</span>
                            <span class="text-sm text-gray-500">/ month</span>
                        </div>
                        <ul class="mt-4 space-y-2 text-sm text-gray-600">
                            <li>20 submissions per month</li>
                            <li>Reading recommendations</li>
                            <li>PDF downloads</li>
                        </ul>
                        <button type="button" class="mt-6 w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            Start Free Trial (1 month)
                        </button>
                    </div>

                    <div class="w-64 shrink-0 rounded-lg border border-indigo-200 bg-indigo-50 p-6 shadow-sm">
                        <div class="text-sm font-semibold text-indigo-700">Gold</div>
                        <div class="mt-3 flex items-end gap-2">
                            <span class="text-3xl font-semibold text-gray-900">$20</span>
                            <span class="text-sm text-gray-500">/ month</span>
                        </div>
                        <ul class="mt-4 space-y-2 text-sm text-gray-700">
                            <li>50 submissions per month</li>
                            <li>Priority recommendation refresh</li>
                            <li>All Silver features</li>
                        </ul>
                        <button type="button" class="mt-6 w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Start Free Trial (1 month)
                        </button>
                    </div>

                    <div class="w-64 shrink-0 rounded-lg border border-gray-200 p-6">
                        <div class="text-sm font-semibold text-gray-700">Premium</div>
                        <div class="mt-3 flex items-end gap-2">
                            <span class="text-3xl font-semibold text-gray-900">$30</span>
                            <span class="text-sm text-gray-500">/ month</span>
                        </div>
                        <ul class="mt-4 space-y-2 text-sm text-gray-600">
                            <li>Unlimited submissions</li>
                            <li>All Gold features</li>
                            <li>Dedicated support</li>
                        </ul>
                        <button type="button" class="mt-6 w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            Start Free Trial (1 month)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
