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
                <label for="child_select_recommendations" class="text-gray-600">Child:</label>
                <select id="child_select_recommendations" name="child_id" class="min-w-[200px] rounded-md border border-gray-300 px-2 py-1 text-sm" onchange="this.form.submit()" required>
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
                <h2 class="text-lg font-semibold text-gray-900">Readings</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Suggestions based on the latest essay submission and the selected child profile.
                </p>

                @if ($errors->has('readings'))
                    <p class="mt-4 text-sm text-red-600">{{ $errors->first('readings') }}</p>
                @endif
                @if ($essayCount > 0)
                    <div class="mt-4 flex justify-end">
                        <form method="POST" action="{{ route('reading-recommendations.refresh') }}">
                            @csrf
                            <input type="hidden" name="child_id" value="{{ $selectedChildId }}">
                            <button type="submit" class="mr-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Refresh
                            </button>
                        </form>
                        <a href="{{ route('reading-recommendations', ['download' => 1, 'child_id' => $selectedChildId]) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Download PDF
                        </a>
                    </div>
                @endif

                @if (! $essayCount)
                    <p class="mt-4 text-sm text-gray-600">Submit an essay to see tailored recommendations.</p>
                @elseif (!empty($isRefreshing))
                    <p class="mt-4 text-sm text-gray-600">Recommendations are being prepared. Please check back soon.</p>
                @else
                    <div class="mt-4">
                        <div class="text-sm font-semibold text-gray-800">Essays Considered</div>
                        <div class="mt-2 text-sm text-gray-600">
                            {{ $essayCount }} submissions analyzed
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-800">Reading List</h3>
                @if (empty($recommendations))
                    <p class="mt-2 text-sm text-gray-600">No recommendations yet.</p>
                @else
                    <ul class="mt-3 space-y-4 text-sm text-gray-700">
                        @foreach ($recommendations as $index => $item)
                            <li class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                                    <span class="rounded-full bg-gray-200 px-2 py-0.5 text-gray-700">
                                        {{ $item['type'] }}
                                    </span>
                                    <span class="font-semibold text-gray-800">{{ $item['title'] }}</span>
                                    <a class="text-blue-700 hover:underline" href="{{ route('reading-recommendations', ['download_item' => $index, 'child_id' => $selectedChildId]) }}">
                                        Download PDF
                                    </a>
                                </div>
                                <p class="mt-2 text-sm text-gray-700">{{ $item['paragraph'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
