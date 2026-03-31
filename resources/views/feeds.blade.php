<x-app-layout>
    @if (!empty($shareItem))
        @push('meta')
            <meta property="og:title" content="Shared essay from {{ $shareItem->child_name ?? 'Child' }}">
            <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($shareItem->corrected_text ?? ''), 140) }}">
            <meta property="og:image" content="{{ $shareItem->image_path ? url(\Illuminate\Support\Facades\Storage::url($shareItem->image_path)) : url('/images/reading-college-logo.svg') }}">
            <meta property="og:url" content="{{ url()->current() }}">
            <meta property="og:type" content="article">
            <meta name="twitter:card" content="summary_large_image">
        @endpush
    @endif
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-6 text-sm font-semibold text-gray-700">
            <a href="{{ route('feeds') }}" class="hover:text-gray-900 {{ request()->routeIs('feeds') ? 'text-gray-900 underline' : '' }}">
                {{ __('Feeds') }}
            </a>
            @if (Auth::check())
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
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Browse shared essays</h2>
                        <p class="mt-1 text-sm text-gray-600">Filter by child name or date range.</p>
                    </div>
                    <form method="GET" action="{{ route('feeds.magazine.download') }}">
                        <input type="hidden" name="child_name" value="{{ $filters['child_name'] ?? '' }}">
                        <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                        <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                        <button class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800" type="submit">
                            Create magazine
                        </button>
                    </form>
                </div>

                <form class="mt-4 grid gap-3 sm:grid-cols-4" method="GET" action="{{ route('feeds') }}">
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
                        <a href="{{ route('feeds') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
            @if ($items->isEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600">
                    No shared essays yet.
                </div>
            @else
                @foreach ($items as $item)
                    @php
                        $shareUrl = route('feeds.show', $item->id);
                    @endphp
                    <div id="shared-{{ $item->id }}" class="rounded-lg border border-gray-200 bg-white p-6">
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <div>
                                <span class="font-semibold text-gray-800">{{ $item->child_name ?? 'Child' }}</span>
                                @if ($item->child_age)
                                    <span class="ml-1 text-gray-500">({{ $item->child_age }})</span>
                                @endif
                            </div>
                            <span>{{ optional($item->shared_at)->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="mt-2 flex items-center gap-3 text-xs font-semibold text-gray-500">
                            <a class="hover:text-blue-600" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener">
                                Share on Facebook
                            </a>
                            <a class="hover:text-gray-800" href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener">
                                Share on X
                            </a>
                        </div>
                        @if ($item->image_path)
                            <img class="mt-4 mx-auto w-full rounded-md border border-gray-200 object-contain" src="{{ \Illuminate\Support\Facades\Storage::url($item->image_path) }}" alt="Shared essay image">
                        @endif
                        @if ($item->corrected_text)
                            <pre class="mt-4 whitespace-pre-wrap text-sm text-gray-700">{{ $item->corrected_text }}</pre>
                        @endif
                    </div>
                @endforeach
                @if ($items instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div>
                        {{ $items->links('pagination::simple-tailwind') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
