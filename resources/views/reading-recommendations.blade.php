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
                {{ __('Reading Recommendations') }}
            </a>
        </div>
        @if (Auth::user()->children->isNotEmpty())
            <form class="mt-4 flex items-center gap-2 text-sm" method="POST" action="{{ route('children.select') }}">
                @csrf
                <label for="child_select_recommendations" class="text-gray-600">Child:</label>
                <select id="child_select_recommendations" name="child_id" class="rounded-md border border-gray-300 px-2 py-1 text-sm" onchange="this.form.submit()" required>
                    @foreach (Auth::user()->children as $child)
                        <option value="{{ $child->id }}" {{ (int) session('selected_child_id') === $child->id ? 'selected' : '' }}>
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
                <h2 class="text-lg font-semibold text-gray-900">Recommended Reading</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Suggestions based on the latest essay submission and the selected child profile.
                </p>

                @if ($child)
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                        <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700">
                            {{ $child->name }}
                        </span>
                        <span class="rounded-full bg-gray-100 px-3 py-1">
                            Age {{ $child->age }}
                        </span>
                        <span class="rounded-full bg-gray-100 px-3 py-1">
                            {{ ucfirst(str_replace('-', ' ', $child->gender)) }}
                        </span>
                    </div>
                @endif

                @if (! $essayCount)
                    <p class="mt-4 text-sm text-gray-600">Submit an essay to see tailored recommendations.</p>
                @else
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($topics as $topic)
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ ucfirst($topic) }}
                            </span>
                        @endforeach
                    </div>
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
                    <ul class="mt-3 space-y-2 text-sm text-gray-700">
                        @foreach ($recommendations as $item)
                            <li class="flex items-start gap-2">
                                <span class="mt-1 h-2 w-2 rounded-full {{ $item['type'] === 'Movie' ? 'bg-amber-500' : 'bg-blue-500' }}"></span>
                                <a class="text-blue-700 hover:underline" href="{{ $item['url'] }}" target="_blank" rel="noopener">
                                    {{ $item['title'] }}
                                </a>
                                <span class="text-xs text-gray-500">({{ $item['type'] }})</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
