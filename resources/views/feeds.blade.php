<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-6 text-sm font-semibold text-gray-700">
            <a href="{{ route('feeds') }}" class="hover:text-gray-900 {{ request()->routeIs('feeds') ? 'text-gray-900 underline' : '' }}">
                {{ __('Feeds') }}
            </a>
            @if (Auth::check())
                <a href="{{ route('dashboard') }}" class="hover:text-gray-900 {{ request()->routeIs('dashboard') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Dashboard') }}
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
                <a href="{{ route('songs') }}" class="hover:text-gray-900 {{ request()->routeIs('songs') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Songs') }}
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
            @if ($items->isEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600">
                    No shared essays yet.
                </div>
            @else
                @foreach ($items as $item)
                    <div class="rounded-lg border border-gray-200 bg-white p-6">
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <div>
                                <span class="font-semibold text-gray-800">{{ $item->child_name ?? 'Child' }}</span>
                                @if ($item->child_age)
                                    <span class="ml-1 text-gray-500">({{ $item->child_age }})</span>
                                @endif
                            </div>
                            <span>{{ optional($item->shared_at)->format('Y-m-d H:i') }}</span>
                        </div>
                        @if ($item->image_path)
                            <img class="mt-4 w-full rounded-md border border-gray-200 object-contain" src="{{ \Illuminate\Support\Facades\Storage::url($item->image_path) }}" alt="Shared essay image">
                        @endif
                        @if ($item->corrected_text)
                            <pre class="mt-4 whitespace-pre-wrap text-sm text-gray-700">{{ $item->corrected_text }}</pre>
                        @endif
                    </div>
                @endforeach
                <div>
                    {{ $items->links('pagination::simple-tailwind') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
