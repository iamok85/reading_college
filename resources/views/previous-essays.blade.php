<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-6 text-sm font-semibold text-gray-700">
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
                <label for="child_select_previous" class="text-gray-600">Child:</label>
                <select id="child_select_previous" name="child_id" class="min-w-[200px] rounded-md border border-gray-300 px-2 py-1 text-sm" onchange="this.form.submit()" required>
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
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                @if ($essays->isEmpty())
                    <p class="text-sm text-gray-600">No submissions yet.</p>
                @else
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            Page {{ $essays->currentPage() }} of {{ $essays->lastPage() }}
                        </div>
                        <div>
                            {{ $essays->onEachSide(0)->links('pagination::simple-tailwind') }}
                        </div>
                    </div>
                    <div class="space-y-4">
                        @foreach ($essays as $essay)
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-600">
                                        Uploaded: {{ \Carbon\Carbon::parse($essay->uploaded_at)->format('Y-m-d H:i') }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('previous-essays', ['download' => $essay->id]) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Save as PDF
                                        </a>
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 hover:bg-red-100 delete-essay-btn"
                                            data-essay-id="{{ $essay->id }}"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600">
                                    
                                </div>
                                @php
                                    $imagePaths = is_array($essay->image_paths)
                                        ? $essay->image_paths
                                        : (json_decode($essay->image_paths, true) ?: []);
                                @endphp
                                @if (!empty($imagePaths))
                                    <div class="mt-3">
                                        <div class="text-sm font-semibold text-gray-800">Attachments</div>
                                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                            @foreach ($imagePaths as $path)
                                                @if (\Illuminate\Support\Str::endsWith($path, '.pdf'))
                                                    <a class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-green-700 hover:bg-gray-100" href="{{ \Illuminate\Support\Facades\Storage::url($path) }}" target="_blank" rel="noopener">
                                                        View PDF
                                                    </a>
                                                @else
                                                    <img class="max-h-64 w-full rounded-md border border-gray-200 object-contain" src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" alt="Essay image">
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                <div class="mt-3">
                                    <div class="text-sm font-semibold text-gray-800">Original Writting</div>
                                    <pre class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $essay->ocr_text }}</pre>
                                </div>
                                <div class="mt-3">
                                    <div class="text-sm font-semibold text-gray-800">Feedback</div>
                                    <pre class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $essay->response_text }}</pre>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
                <div id="delete-overlay" class="absolute inset-0 bg-black/50"></div>
                <div class="relative z-10 w-full max-w-sm rounded-lg bg-white p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Delete essay?</h3>
                    <p class="mt-2 text-sm text-gray-600">This action can’t be undone.</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" id="delete-cancel" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('previous-essays.delete') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="essay_id" id="delete-essay-id">
                            <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    (function () {
        const modal = document.getElementById('delete-modal');
        const overlay = document.getElementById('delete-overlay');
        const cancelBtn = document.getElementById('delete-cancel');
        const input = document.getElementById('delete-essay-id');
        const openButtons = document.querySelectorAll('.delete-essay-btn');

        const openModal = (id) => {
            if (input) input.value = id;
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        };

        const closeModal = () => {
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        };

        openButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-essay-id');
                openModal(id);
            });
        });

        overlay?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);
    })();
</script>
