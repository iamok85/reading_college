<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://unpkg.com/flowbite@1.4.0/dist/flowbite.js"></script>

        <!-- Styles -->
        @livewireStyles
        <link rel="stylesheet" href="https://unpkg.com/flowbite@1.4.4/dist/flowbite.min.css" />
    </head>
    <body class="font-sans antialiased">
        <x-banner />

        <div class="min-h-screen bg-gray-100">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @if (Auth::check() && ! Auth::user()->children()->exists())
            <div id="child-profile-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
                <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-lg font-semibold text-gray-900">Tell us about your child</h2>
                    <p class="mt-2 text-sm text-gray-600">Please add your child’s details to personalize recommendations.</p>

                    <form class="mt-6 space-y-4" method="POST" action="{{ route('children.store') }}">
                        @csrf
                        <div>
                            <label for="child_name_modal" class="block text-sm font-medium text-gray-700">Child's Name</label>
                            <input id="child_name_modal" name="child_name" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="child_age_modal" class="block text-sm font-medium text-gray-700">Child's Birth Year</label>
                            <input id="child_age_modal" name="child_birth_year" type="number" min="{{ now()->year - 18 }}" max="{{ now()->year - 1 }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="child_gender_modal" class="block text-sm font-medium text-gray-700">Child's Gender</label>
                            <select id="child_gender_modal" name="child_gender" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="" disabled selected>Select</option>
                                <option value="female">Female</option>
                                <option value="male">Male</option>
                                <option value="non-binary">Non-binary</option>
                                <option value="prefer-not-to-say">Prefer not to say</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-3">
                            <a href="{{ url('/') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @stack('modals')

        @livewireScripts
    </body>
</html>
