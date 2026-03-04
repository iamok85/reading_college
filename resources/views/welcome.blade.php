<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-935P1VWT6J"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-935P1VWT6J');
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-white flex flex-col">
        <header class="border-b border-gray-100">
            <div class="mx-auto flex max-w-6xl items-start justify-between gap-6 px-6 py-4 text-sm">
                <div>
                    <div class="flex items-center gap-2 text-base font-semibold text-gray-900">
                        <img src="/images/reading-college-logo.svg" alt="Reading College logo" class="h-8 w-auto" />
                        Reading College
                        <a href="{{ route('investor') }}" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700 hover:bg-gray-200">
                            Investor
                        </a>
                    </div>
                    <div class="mt-1 text-xs text-gray-500">
                        Track writing growth, recommend readings, and turn essays into creative media.
                    </div>
                </div>
                <div class="flex-1"></div>
                <nav class="flex flex-wrap items-center gap-6 text-sm font-semibold text-gray-700">
                    <a href="{{ route('feeds') }}" class="hover:text-gray-900 {{ request()->routeIs('feeds') ? 'text-gray-900 underline' : '' }}">
                        Feeds
                    </a>
                    <a href="{{ route('about') }}" class="hover:text-gray-900 {{ request()->routeIs('about') ? 'text-gray-900 underline' : '' }}">
                        About
                    </a>
                    <a href="{{ route('contact') }}" class="hover:text-gray-900 {{ request()->routeIs('contact') ? 'text-gray-900 underline' : '' }}">
                        Contact
                    </a>
                    <a href="{{ route('demo') }}" class="hover:text-gray-900 {{ request()->routeIs('demo') ? 'text-gray-900 underline' : '' }}">
                        Demo
                    </a>
                    <a href="{{ route('plans') }}" class="hover:text-gray-900 {{ request()->routeIs('plans') ? 'text-gray-900 underline' : '' }}">
                        Plans
                    </a>
                </nav>
                <div class="flex items-center gap-3">
                    <a href="{{ route('login.clean') }}" class="rounded-md px-3 py-1.5 font-semibold text-gray-700 hover:text-gray-900" style="border: 1px solid #e5e7eb;">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="rounded-md bg-blue-600 px-3 py-1.5 font-semibold text-white hover:bg-blue-500" style="background-color: #2563eb; color: #ffffff;">
                        Register
                    </a>
                </div>
            </div>
        </header>
        <main class="flex flex-1 flex-col items-center px-6 py-12">
            <div class="max-w-3xl text-center">
                <h1 class="text-3xl font-semibold text-gray-900">
                    Share kids’ writing wins with the community.
                </h1>
                <p class="mt-3 text-base text-gray-600">
                    Browse real corrected essays and illustrations shared by families, and see what learning looks like in action.
                </p>
            </div>

            <div class="mt-10 w-full max-w-5xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Latest shared essays</h2>
                    <a class="text-sm font-semibold text-blue-600 hover:text-blue-500" href="{{ route('feeds') }}">
                        View all
                    </a>
                </div>

                @if (empty($items) || $items->isEmpty())
                    <div class="mt-4 rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600">
                        No shared essays yet.
                    </div>
                @else
                    <div class="mt-4 grid gap-6 md:grid-cols-3">
                        @foreach ($items as $item)
                            <div class="rounded-lg border border-gray-200 bg-white p-5">
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <div class="font-semibold text-gray-700">
                                        {{ $item->child_name ?? 'Child' }}
                                    </div>
                                    <span>{{ optional($item->shared_at)->format('Y-m-d') }}</span>
                                </div>
                                @if ($item->image_path)
                                    <img
                                        class="mt-3 mx-auto rounded-md border border-gray-200 object-cover"
                                        style="width: 250px; height: 250px;"
                                        src="{{ \Illuminate\Support\Facades\Storage::url($item->image_path) }}"
                                        alt="Shared essay image"
                                    >
                                @endif
                                @if ($item->corrected_text)
                                    <p class="mt-3 text-sm text-gray-600 line-clamp-4">
                                        {{ $item->corrected_text }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </main>
    </body>
</html>
