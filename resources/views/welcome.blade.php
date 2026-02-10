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
                <div class="flex items-center gap-2 text-base font-semibold text-gray-900">
                    <img src="/images/reading-college-logo.svg" alt="Reading College logo" class="h-8 w-auto" />
                    Reading College
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
            <div class="max-w-2xl text-center">
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
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
                    Help your kids grow their writing skills.
                </h1>
                <p class="mt-4 text-base text-gray-600">
                    Reading College helps families track improvements in kids' writing, automatically recommends reading materials, and can turn their essays into images, songs, or even short movies to make learning more vivid and interesting.
                </p>
            </div>
        </main>
    </body>
</html>
