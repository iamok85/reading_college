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
                @elseif ($errors->has('credits'))
                    <p class="mt-4 text-sm text-red-600">{{ $errors->first('credits') }}</p>
                @elseif (!empty($refreshError))
                    <p class="mt-4 text-sm text-red-600">{{ $refreshError }}</p>
                @endif
                @if ($essayCount > 0)
                    <div class="mt-4 flex justify-end">
                        <form method="POST" action="{{ route('reading-recommendations.refresh') }}" id="readings-refresh-form">
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
                    <ul class="mt-3 space-y-4 text-sm text-gray-700" id="reading-recommendation-list">
                        @foreach ($recommendations as $index => $item)
                            <li class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700" data-rec-index="{{ $index }}">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                                    <span class="rounded-full bg-gray-200 px-2 py-0.5 text-gray-700">
                                        {{ $item['type'] }}
                                    </span>
                                    <span class="font-semibold text-gray-800">{{ $item['title'] }}</span>
                                    <a class="text-blue-700 hover:underline" href="{{ route('reading-recommendations', ['download_item' => $index, 'child_id' => $selectedChildId]) }}">
                                        Download PDF
                                    </a>
                                </div>
                                @if (!empty($item['image_path']))
                                    <img class="mt-3 mx-auto rounded-md border border-gray-200 object-contain" style="width: 250px; height: 250px;" src="{{ \Illuminate\Support\Facades\Storage::url($item['image_path']) }}" alt="Reading recommendation image" data-rec-image="1">
                                    <p class="mt-2 text-sm text-gray-700">{{ $item['paragraph'] }}</p>
                                @else
                                    <div class="mt-3 text-xs text-gray-500" data-rec-placeholder>Generating image…</div>
                                    <p class="mt-2 text-sm text-gray-700">{{ $item['paragraph'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div id="readings-loading-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 text-center shadow-lg">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
            <p class="mt-4 text-sm font-semibold text-gray-800">Refreshing readings…</p>
            <p class="mt-1 text-xs text-gray-500">Please wait a moment.</p>
        </div>
    </div>

    <script>
        (function () {
            const wasRefreshingOnLoad = @json(!empty($isRefreshing));
            const list = document.getElementById('reading-recommendation-list');

            const poll = () => {
                fetch("{{ route('reading-recommendations.status', ['child_id' => $selectedChildId]) }}", {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((response) => response.ok ? response.json() : null)
                    .then((data) => {
                        if (!data || !Array.isArray(data.items)) return;
                        if (wasRefreshingOnLoad && data.status === 'completed') {
                            window.location.reload();
                            return;
                        }

                        if (wasRefreshingOnLoad && data.status === 'failed') {
                            window.location.reload();
                            return;
                        }

                        if (!list) return;
                        data.items.forEach((item, index) => {
                            const row = list.querySelector(`[data-rec-index="${index}"]`);
                            if (!row) return;
                            const existing = row.querySelector('img[data-rec-image]');

                            if (item.image_path && !existing) {
                                const img = document.createElement('img');
                                img.className = 'mt-3 mx-auto rounded-md border border-gray-200 object-contain';
                                img.style.width = '250px';
                                img.style.height = '250px';
                                img.src = "{{ \Illuminate\Support\Facades\Storage::url('') }}" + item.image_path;
                                img.alt = 'Reading recommendation image';
                                img.setAttribute('data-rec-image', '1');

                                const placeholder = row.querySelector('[data-rec-placeholder]');
                                if (placeholder) {
                                    placeholder.replaceWith(img);
                                } else {
                                    const paragraph = row.querySelector('p');
                                    if (paragraph) {
                                        row.insertBefore(img, paragraph);
                                    } else {
                                        const text = document.createElement('p');
                                        text.className = 'mt-2 text-sm text-gray-700';
                                        text.textContent = item.paragraph || '';
                                        row.appendChild(img);
                                        row.appendChild(text);
                                    }
                                }
                            } else if (item.image_path && existing) {
                                const placeholder = row.querySelector('[data-rec-placeholder]');
                                if (placeholder) {
                                    placeholder.remove();
                                }
                            }
                        });
                    })
                    .catch(() => {});
            };

            poll();
            setInterval(poll, 5000);
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('readings-refresh-form');
            const modal = document.getElementById('readings-loading-modal');
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
