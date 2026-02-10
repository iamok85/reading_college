<x-guest-layout>
    <div class="min-h-screen bg-gray-50">
        <header class="border-b bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <a class="flex items-center gap-2 text-lg font-semibold text-gray-900" href="{{ url('/') }}">
                    <img src="/images/reading-college-logo.svg" alt="Reading College logo" class="h-8 w-auto" />
                    Reading College
                </a>
                <nav class="flex items-center gap-4 text-sm font-medium text-gray-600">
                    <a class="hover:text-gray-900" href="{{ route('about') }}">About</a>
                    <a class="hover:text-gray-900" href="{{ route('contact') }}">Contact</a>
                    <a class="hover:text-gray-900" href="{{ route('login') }}">Sign in</a>
                    <a class="rounded-md bg-gray-900 px-3 py-1.5 text-white hover:bg-gray-800" href="{{ route('register') }}">Register</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-12">
            <div class="grid gap-8 lg:grid-cols-[1fr_0.9fr]">
                <section class="rounded-2xl bg-white p-8 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Contact</p>
                    <h1 class="mt-2 text-3xl font-semibold text-gray-900">We are here to help.</h1>
                    <p class="mt-4 text-base text-gray-600">
                        Send us a message and we will get back to you within two business days.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase text-gray-500">Email</p>
                            <p class="mt-2 text-sm font-medium text-gray-900">ljingding@gmail.com</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase text-gray-500">Hours</p>
                            <p class="mt-2 text-sm font-medium text-gray-900">Mon–Fri, 9:00 AM–5:00 PM</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase text-gray-500">Mobile</p>
                            <p class="mt-2 text-sm font-medium text-gray-900">0406 912 696</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase text-gray-500">WeChat</p>
                            <p class="mt-2 text-sm font-medium text-gray-900">dljjjj</p>
                        </div>
                    </div>
                </section>

                <aside class="rounded-2xl bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold text-gray-900">Contact form</h2>
                    @if (session('status'))
                        <div class="mt-3 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif
                    <p class="mt-2 text-sm text-gray-600">Prefer email? Use this form and we will reply promptly.</p>
                    <form class="mt-6 space-y-4" action="{{ route('contact.store') }}" method="post">
                        @csrf
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                        <div>
                            <label class="text-sm font-medium text-gray-700" for="name">Name</label>
                            <input class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:ring-gray-900" id="name" name="name" type="text" placeholder="Your name" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700" for="email">Email</label>
                            <input class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:ring-gray-900" id="email" name="email" type="email" placeholder="you@example.com" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700" for="message">Message</label>
                            <textarea class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:ring-gray-900" id="message" name="message" rows="4" placeholder="How can we help?"></textarea>
                        </div>
                        <div class="flex justify-center">
                            <button
                                class="rounded-md px-4 text-sm font-semibold text-white"
                                style="background-color: #2563eb; border: 1px solid #1d4ed8; height: 40px; width: 80px;"
                                type="submit"
                            >
                                Submit
                            </button>
                        </div>
                    </form>
                    <p class="mt-4 text-xs text-gray-500">Your message will be saved for follow-up.</p>
                    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
                    <script>
                        grecaptcha.ready(function () {
                            grecaptcha.execute("{{ config('services.recaptcha.site_key') }}", { action: "contact" })
                                .then(function (token) {
                                    var input = document.getElementById('recaptcha_token');
                                    if (input) {
                                        input.value = token;
                                    }
                                });
                        });
                    </script>
                </aside>
            </div>
        </main>
    </div>
</x-guest-layout>
