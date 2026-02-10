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
            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                <section class="rounded-2xl bg-white p-8 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">About us</p>
                    <h1 class="mt-2 text-3xl font-semibold text-gray-900">Helping Kids Read, Write, and Grow with AI</h1>
                    <p class="mt-4 text-base text-gray-600">
                        The Vision
                    </p>
                    <p class="mt-3 text-sm text-gray-600">
                        Reading College is an educational platform currently in development, powered by Large Language Models (LLMs) to help children improve their reading and writing skills in a fun, personalised, and supportive way.
                    </p>

                    <h2 class="mt-8 text-lg font-semibold text-gray-900">What the Platform Will Do</h2>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li>Personalised reading practice tailored to each child</li>
                        <li>Interactive writing guidance and feedback</li>
                        <li>Age-appropriate learning conversations powered by AI</li>
                        <li>Creative activities that build confidence and critical thinking</li>
                        <li>A safe and supportive digital learning environment</li>
                        <li>Making short cartoon videos and songs from kids’ essays</li>
                    </ul>
                    <p class="mt-4 text-sm text-gray-600">
                        This feature helps make kids excited to become movie script writers or songwriters, inspiring them to write more, express their ideas creatively, and enjoy the writing process.
                    </p>

                    <h2 class="mt-8 text-lg font-semibold text-gray-900">Why It Matters</h2>
                    <p class="mt-3 text-sm text-gray-600">
                        Strong literacy skills help shape a child’s future opportunities and confidence. Reading College aims to make high-quality literacy support accessible to more families through responsible AI technology.
                    </p>

                    <h2 class="mt-8 text-lg font-semibold text-gray-900">Investment Opportunity</h2>
                    <p class="mt-3 text-sm text-gray-600">
                        The platform is currently in development, and Reading College is looking for early supporters and potential investors who believe in improving education through technology.
                    </p>
                </section>

                <aside class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-gray-900">Our mission</h2>
                        <p class="mt-3 text-sm text-gray-600">
                            Make writing feedback timely, actionable, and easy to understand so every student can grow with confidence.
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-gray-900">Get in touch</h2>
                        <p class="mt-3 text-sm text-gray-600">Questions about the platform? We would love to hear from you.</p>
                        <a class="mt-4 inline-flex items-center text-sm font-semibold text-gray-900 hover:underline" href="{{ route('contact') }}">
                            Contact us
                        </a>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</x-guest-layout>
