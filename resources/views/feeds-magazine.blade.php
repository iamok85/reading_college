<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-6 text-sm font-semibold text-gray-700">
            <a href="{{ route('feeds') }}" class="hover:text-gray-900 {{ request()->routeIs('feeds') ? 'text-gray-900 underline' : '' }}">
                {{ __('Feeds') }}
            </a>
            <a href="{{ route('feeds.magazine') }}" class="hover:text-gray-900 {{ request()->routeIs('feeds.magazine') ? 'text-gray-900 underline' : '' }}">
                {{ __('Magazine') }}
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

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-gray-900">Build a magazine PDF</h2>
                <p class="mt-2 text-sm text-gray-600">Filter shared essays and choose the ones you want to include.</p>

                <form class="mt-4 grid gap-3 sm:grid-cols-4" method="GET" action="{{ route('feeds.magazine') }}">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-gray-600" for="child_name">Child name</label>
                        <input
                            id="child_name"
                            name="child_name"
                            type="text"
                            value="{{ $filters['child_name'] ?? '' }}"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                            placeholder="Search by child name"
                        >
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-600" for="date_from">From</label>
                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            value="{{ $filters['date_from'] ?? '' }}"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                        >
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-600" for="date_to">To</label>
                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="{{ $filters['date_to'] ?? '' }}"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                        >
                    </div>
                    <div class="sm:col-span-4 flex items-center gap-3">
                        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            Apply filters
                        </button>
                        <a href="{{ route('feeds.magazine') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <form method="POST" action="{{ route('feeds.magazine.download') }}" class="space-y-4">
                @csrf
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Select the feeds to include, then generate the PDF.
                    </div>
                    <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Generate PDF
                    </button>
                </div>
                @error('selected')
                    <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ $message }}
                    </div>
                @enderror

                @if ($items->isEmpty())
                    <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600">
                        No shared essays found.
                    </div>
                @else
                    <div class="grid gap-4">
                        @foreach ($items as $item)
                            <label class="flex gap-4 rounded-lg border border-gray-200 bg-white p-4">
                                <input type="checkbox" name="selected[]" value="{{ $item->id }}" class="mt-1">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between text-xs text-gray-500">
                                        <span class="font-semibold text-gray-700">{{ $item->child_name ?? 'Child' }}</span>
                                        <span>{{ optional($item->shared_at)->format('Y-m-d') }}</span>
                                    </div>
                                    @if ($item->image_path)
                                        <img class="mt-2 h-32 w-full rounded-md border border-gray-200 object-cover" src="{{ \Illuminate\Support\Facades\Storage::url($item->image_path) }}" alt="Shared essay image">
                                    @endif
                                    @if ($item->corrected_text)
                                        <p class="mt-2 text-sm text-gray-600 line-clamp-3">{{ $item->corrected_text }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <div>
                        {{ $items->links('pagination::simple-tailwind') }}
                    </div>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
