<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

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
                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}" class="rounded-md px-3 py-1.5 font-semibold text-gray-700 hover:text-gray-900" style="border: 1px solid #e5e7eb;">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="rounded-md bg-blue-600 px-3 py-1.5 font-semibold text-white hover:bg-blue-500" style="background-color: #2563eb; color: #ffffff;">
                        Register
                    </a>
                </div>
            </div>
        </header>
        <main class="flex flex-1 items-center justify-center px-6">
            <div class="-mt-[400px] max-w-2xl text-center">
                <div class="-mt-24 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('about') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:border-gray-300">
                        About
                    </a>
                    <a href="{{ route('contact') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:border-gray-300">
                        Contact
                    </a>
                    <a href="{{ route('demo') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:border-gray-300">
                        Demo
                    </a>
                </div>
                <h1 class="mt-6 text-3xl font-semibold text-gray-900">
                    Writting Tracking · Reading Recommendation · Essay Share · Song Movie Scripting
                </h1>

                <div class="mt-10">
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
        </main>
    </body>
</html>
