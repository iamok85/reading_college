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
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://unpkg.com/flowbite@1.4.0/dist/flowbite.js"></script>

        @stack('meta')

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

        @auth
            @php
                $activeEssayJobId = (int) session('active_essay_job_id', 0);
                $dismissedEssayId = (int) session('dismissed_essay_job_id', 0);
                $activeEssayJob = \App\Models\EssaySubmission::query()
                    ->where('user_id', auth()->id())
                    ->when($activeEssayJobId > 0, fn ($query) => $query->where('id', $activeEssayJobId))
                    ->first();

                if (! $activeEssayJob) {
                    $activeEssayJob = \App\Models\EssaySubmission::query()
                        ->where('user_id', auth()->id())
                        ->when($dismissedEssayId > 0, fn ($query) => $query->where('id', '!=', $dismissedEssayId))
                        ->whereIn('processing_status', ['queued', 'processing', 'processing_ocr', 'processing_correction', 'processing_images', 'processing_analysis', 'completed', 'failed'])
                        ->latest('id')
                        ->first();
                }

                $initialEssayJob = $activeEssayJob
                    ? [
                        'id' => $activeEssayJob->id,
                        'status' => $activeEssayJob->processing_status,
                        'error' => $activeEssayJob->processing_error,
                        'view_url' => route('essay-uploaded', ['essay' => $activeEssayJob->id]),
                    ]
                    : null;
            @endphp

            <div
                id="essay-job-panel"
                data-initial-job="{{ e(json_encode($initialEssayJob)) }}"
                class="fixed bottom-6 right-6 z-50 {{ $initialEssayJob ? '' : 'hidden' }} w-[22rem] max-w-[calc(100vw-2rem)]"
            >
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl ring-1 ring-black/5">
                    <div class="flex items-start justify-between border-b border-gray-100 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Essay Upload Job</p>
                            <p id="essay-job-number" class="text-xs text-gray-500">{{ $initialEssayJob ? 'Essay #' . $initialEssayJob['id'] : '' }}</p>
                        </div>
                        <button
                            id="essay-job-close"
                            type="button"
                            class="rounded-md px-2 py-1 text-xs text-gray-500 hover:bg-gray-100 hover:text-gray-700 {{ $initialEssayJob && in_array($initialEssayJob['status'], ['completed', 'failed'], true) ? '' : 'hidden' }}"
                        >
                            Close
                        </button>
                    </div>
                    <div id="essay-job-body" class="px-4 py-4">
                        @if ($initialEssayJob)
                            @php
                                $initialStatus = $initialEssayJob['status'] ?? null;
                                $initialMessage = match ($initialStatus) {
                                    'processing_ocr' => 'Running OCR on the uploaded files...',
                                    'processing_correction' => 'Correcting the essay...',
                                    'processing_images' => 'Generating images for the essay...',
                                    'processing_analysis' => 'Preparing the analysis...',
                                    'processing' => 'Processing the essay...',
                                    'queued' => 'Queued. Waiting for the worker to start...',
                                    'completed' => 'Upload finished. Your essay is ready to view.',
                                    'failed' => $initialEssayJob['error'] ?: 'Background processing failed.',
                                    default => 'Waiting for status...',
                                };
                            @endphp

                            @if (in_array($initialStatus, ['queued', 'processing', 'processing_ocr', 'processing_correction', 'processing_images', 'processing_analysis'], true))
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 animate-spin text-blue-500" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-800">{{ $initialMessage }}</p>
                                        <p class="mt-1 text-xs text-gray-500">You can keep using the page while this runs.</p>
                                    </div>
                                </div>
                            @elseif ($initialStatus === 'completed')
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-green-100 text-green-700">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3.2-3.2a1 1 0 111.414-1.42l2.493 2.494 6.493-6.494a1 1 0 011.415 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-800">{{ $initialMessage }}</p>
                                        <div class="mt-3 flex justify-end">
                                            <a href="{{ $initialEssayJob['view_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                                View Essay Uploaded
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($initialStatus === 'failed')
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-red-700">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-10.293a1 1 0 00-1.414-1.414L10 8.586 7.707 6.293A1 1 0 106.293 7.707L8.586 10l-2.293 2.293a1 1 0 101.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-red-700">Essay processing failed</p>
                                        <p class="mt-1 text-sm text-red-600">{{ $initialMessage }}</p>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-gray-500">{{ $initialMessage }}</p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endauth

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
        @auth
            <script>
                (function () {
                    const panel = document.getElementById('essay-job-panel');
                    const body = document.getElementById('essay-job-body');
                    const number = document.getElementById('essay-job-number');
                    const closeButton = document.getElementById('essay-job-close');
                    const currentUrl = "{{ route('essay-jobs.current') }}";
                    const dismissUrl = "{{ route('essay-jobs.dismiss') }}";
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const initialJob = JSON.parse(panel.dataset.initialJob || 'null');

                    if (!panel || !body || !number || !closeButton || !csrfToken) {
                        return;
                    }

                    let dismissedLocally = false;
                    let currentJobId = null;
                    const storageKey = 'reading_college_active_essay_job_id';

                    const activeStatuses = ['queued', 'processing', 'processing_ocr', 'processing_correction', 'processing_analysis'];

                    const escapeHtml = (value) => {
                        const div = document.createElement('div');
                        div.textContent = value ?? '';
                        return div.innerHTML;
                    };

                    const statusLabel = (job) => {
                        switch (job.status) {
                            case 'processing_ocr':
                                return 'Running OCR on the uploaded files...';
                            case 'processing_correction':
                                return 'Correcting the essay...';
                            case 'processing_analysis':
                                return 'Preparing the analysis...';
                            case 'processing':
                                return 'Processing the essay...';
                            case 'queued':
                                return 'Queued. Waiting for the worker to start...';
                            case 'completed':
                                return 'Upload finished. Your essay is ready to view.';
                            case 'failed':
                                return job.error || 'Background processing failed.';
                            default:
                                return 'Waiting for status...';
                        }
                    };

                    const render = (job) => {
                        if (!job) {
                            currentJobId = null;
                            dismissedLocally = false;
                            panel.classList.add('hidden');
                            return;
                        }

                        if (currentJobId !== job.id) {
                            currentJobId = job.id;
                            dismissedLocally = false;
                        }

                        if (dismissedLocally) {
                            panel.classList.add('hidden');
                            return;
                        }

                        number.textContent = `Essay #${job.id}`;

                        if (activeStatuses.includes(job.status) || !job.status) {
                            closeButton.classList.add('hidden');
                            body.innerHTML = `
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 animate-spin text-blue-500" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-800">${escapeHtml(statusLabel(job))}</p>
                                        <p class="mt-1 text-xs text-gray-500">You can keep using the page while this runs.</p>
                                    </div>
                                </div>
                            `;
                        } else if (job.status === 'completed') {
                            closeButton.classList.remove('hidden');
                            body.innerHTML = `
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-green-100 text-green-700">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3.2-3.2a1 1 0 111.414-1.42l2.493 2.494 6.493-6.494a1 1 0 011.415 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-800">${escapeHtml(statusLabel(job))}</p>
                                        <div class="mt-3 flex justify-end">
                                            <a href="${escapeHtml(job.view_url)}" target="_blank" rel="noopener" class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                                View Essay Uploaded
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else if (job.status === 'failed') {
                            closeButton.classList.remove('hidden');
                            body.innerHTML = `
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-red-700">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-10.293a1 1 0 00-1.414-1.414L10 8.586 7.707 6.293A1 1 0 106.293 7.707L8.586 10l-2.293 2.293a1 1 0 101.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-red-700">Essay processing failed</p>
                                        <p class="mt-1 text-sm text-red-600">${escapeHtml(statusLabel(job))}</p>
                                    </div>
                                </div>
                            `;
                        } else {
                            closeButton.classList.remove('hidden');
                            body.innerHTML = `<p class="text-sm text-gray-500">${escapeHtml(statusLabel(job))}</p>`;
                        }

                        panel.classList.remove('hidden');
                    };

                    const poll = async () => {
                        try {
                            const storedEssayId = window.localStorage.getItem(storageKey);
                            const url = new URL(currentUrl, window.location.origin);
                            if (storedEssayId) {
                                url.searchParams.set('essay_id', storedEssayId);
                            }

                            const response = await fetch(url.toString(), {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (!response.ok) {
                                return;
                            }

                            const data = await response.json();
                            if (data.job?.id) {
                                window.localStorage.setItem(storageKey, String(data.job.id));
                            } else {
                                window.localStorage.removeItem(storageKey);
                            }
                            render(data.job ?? null);
                        } catch (error) {
                        }
                    };

                    const startPolling = () => {
                        if (initialJob?.id) {
                            currentJobId = Number(initialJob.id);
                            window.localStorage.setItem(storageKey, String(initialJob.id));
                            render(initialJob);
                        }

                        poll();
                        window.setInterval(poll, 5000);
                    };

                    closeButton.addEventListener('click', async () => {
                        dismissedLocally = true;
                        panel.classList.add('hidden');
                        window.localStorage.removeItem(storageKey);

                        try {
                            await fetch(dismissUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({}),
                            });
                        } catch (error) {
                        }
                    });

                    const handleEssayJobStarted = (event) => {
                        const payload = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                        const essayId = payload?.essayId;
                        if (!essayId) {
                            return;
                        }

                        currentJobId = Number(essayId);
                        dismissedLocally = false;
                        window.localStorage.setItem(storageKey, String(essayId));

                        render({
                            id: Number(essayId),
                            status: 'queued',
                            error: null,
                            view_url: '',
                        });
                    };

                    window.addEventListener('essay-job-started', handleEssayJobStarted);
                    document.addEventListener('essay-job-started', handleEssayJobStarted);

                    startPolling();
                })();
            </script>
        @endauth
    </body>
</html>
