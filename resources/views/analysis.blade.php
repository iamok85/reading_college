<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-6 text-sm font-semibold text-gray-700">
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
        </div>
        @if (Auth::user()->children->isNotEmpty())
            <form class="mt-4 flex items-center gap-2 text-sm" method="GET" action="{{ url()->current() }}">
                <label for="child_select_analysis" class="text-gray-600">Child:</label>
                <select id="child_select_analysis" name="child_id" class="min-w-[200px] rounded-md border border-gray-300 px-2 py-1 text-sm" onchange="this.form.submit()" required>
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
        <div class="max-w-5xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Analysis</h2>
                        <p class="mt-2 text-sm text-gray-600">Psychological insights from the last 5 essays.</p>
                    </div>
                    @if ($essayCount > 0)
                        <div class="flex flex-wrap items-center gap-3">
                            <form method="POST" action="{{ route('analysis.refresh') }}" id="analysis-refresh-form">
                                @csrf
                                <input type="hidden" name="child_id" value="{{ $selectedChildId }}">
                                <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Refresh
                                </button>
                            </form>
                            <a href="{{ route('analysis', ['download' => 1, 'child_id' => $selectedChildId]) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Download PDF
                            </a>
                        </div>
                    @endif
                </div>

                @if ($errors->has('analysis'))
                    <p class="mt-4 text-sm text-red-600">{{ $errors->first('analysis') }}</p>
                @endif

                @if (! $essayCount)
                    <p class="mt-4 text-sm text-gray-600">Submit essays to generate analysis.</p>
                @elseif (!empty($isRefreshing))
                    <p class="mt-4 text-sm text-gray-600">Analysis is being prepared. Please check back soon.</p>
                @elseif ($analysis)
                    @php
                        $analysisFormatted = preg_replace('/^Summary\\s*:?\\s*/mi', "Summary:\n", $analysis);
                    @endphp
                    <pre class="mt-4 max-w-full whitespace-pre-wrap break-words text-sm text-gray-700">{{ $analysisFormatted }}</pre>
                @else
                    <p class="mt-4 text-sm text-gray-600">Analysis is not available yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div id="analysis-loading-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 text-center shadow-lg">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
            <p class="mt-4 text-sm font-semibold text-gray-800">Refreshing analysis…</p>
            <p class="mt-1 text-xs text-gray-500">Please wait a moment.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('analysis-refresh-form');
            const modal = document.getElementById('analysis-loading-modal');
            if (!form || !modal) {
                return;
            }

            form.addEventListener('submit', () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });
    </script>
</x-app-layout>
