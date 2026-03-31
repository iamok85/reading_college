<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-6 text-sm font-semibold text-gray-700">
            <a href="{{ route('feeds') }}" class="hover:text-gray-900 {{ request()->routeIs('feeds') ? 'text-gray-900 underline' : '' }}">
                {{ __('Feeds') }}
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
            @endauth
        </div>
        @if (Auth::user()->children->isNotEmpty())
            <form class="mt-4 flex items-center gap-2 text-sm" method="GET" action="{{ url()->current() }}">
                <label for="child_select_jobs" class="text-gray-600">Child:</label>
                <select id="child_select_jobs" name="child_id" class="min-w-[200px] rounded-md border border-gray-300 px-2 py-1 text-sm" onchange="this.form.submit()" required>
                    @foreach (Auth::user()->children as $child)
                        <option value="{{ $child->id }}" {{ (int) ($selectedChildId ?? session('selected_child_id')) === $child->id ? 'selected' : '' }}>
                            {{ $child->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Essay Processing</h2>
                        <p class="mt-2 text-sm text-gray-600">Track uploaded essays as they move through OCR, correction, image generation, and analysis.</p>
                    </div>
                    <a href="{{ route('dashboard', ['child_id' => $selectedChildId]) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Back to Dashboard
                    </a>
                </div>

                @if ($jobs->isEmpty())
                    <p class="mt-6 text-sm text-gray-600">No essay jobs yet.</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Essay</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Child</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Uploaded</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Error</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($jobs as $job)
                                    @php
                                        $status = (string) ($job->processing_status ?? 'queued');
                                        $statusClasses = match ($status) {
                                            'completed' => 'bg-green-100 text-green-700',
                                            'failed' => 'bg-red-100 text-red-700',
                                            'processing_ocr', 'processing_correction', 'processing_images', 'processing_analysis', 'processing' => 'bg-blue-100 text-blue-700',
                                            default => 'bg-amber-100 text-amber-700',
                                        };
                                        $statusLabel = match ($status) {
                                            'processing_ocr' => 'Processing OCR',
                                            'processing_correction' => 'Correcting',
                                            'processing_images' => 'Generating Images',
                                            'processing_analysis' => 'Analyzing',
                                            default => \Illuminate\Support\Str::headline(str_replace('_', ' ', $status)),
                                        };
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-4 text-gray-800">#{{ $job->id }}</td>
                                        <td class="px-4 py-4 text-gray-600">{{ $job->child?->name ?? 'All children' }}</td>
                                        <td class="px-4 py-4 text-gray-600">{{ optional($job->uploaded_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td class="max-w-xs px-4 py-4 text-gray-600">
                                            <div class="truncate" title="{{ $job->processing_error }}">{{ $job->processing_error ?: '—' }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            @if ($job->processing_status === 'completed')
                                                <a href="{{ route('previous-essays', ['child_id' => $selectedChildId]) }}" class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                                    View
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400">Waiting</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $jobs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @php
        $hasActiveJobs = $jobs->contains(fn ($job) => in_array($job->processing_status, ['queued', 'processing', 'processing_ocr', 'processing_correction', 'processing_images', 'processing_analysis'], true));
    @endphp

    @if ($hasActiveJobs)
        <script>
            setTimeout(() => window.location.reload(), 5000);
        </script>
    @endif
</x-app-layout>
