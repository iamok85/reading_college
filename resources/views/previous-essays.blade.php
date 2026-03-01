<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-6 text-sm font-semibold text-gray-700">
            <a href="{{ route('feeds') }}" class="hover:text-gray-900 {{ request()->routeIs('feeds') ? 'text-gray-900 underline' : '' }}">
                {{ __('Feeds') }}
            </a>
            @auth
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
            @endauth
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
                                        @if (in_array($essay->id, $sharedEssayIds ?? [], true))
                                            <form method="POST" action="{{ route('previous-essays.unshare', $essay->id) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Unshare
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('previous-essays.share', $essay->id) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center rounded-md border border-blue-300 bg-blue-50 px-3 py-2 text-sm text-blue-700 hover:bg-blue-100">
                                                    Share
                                                </button>
                                            </form>
                                        @endif
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
                                    $generatedImagePaths = is_array($essay->generated_image_paths)
                                        ? $essay->generated_image_paths
                                        : (json_decode($essay->generated_image_paths, true) ?: []);
                                @endphp
                                <div class="mt-4" data-tab-group="essay-{{ $essay->id }}">
                                    <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-2">
                                        <button type="button" class="tab-btn rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white" data-tab-target="original">Original Text</button>
                                        <button type="button" class="tab-btn rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200" data-tab-target="attachments">Attachments</button>
                                        <button type="button" class="tab-btn rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200" data-tab-target="spelling">Spelling</button>
                                        <button type="button" class="tab-btn rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200" data-tab-target="grammar">Grammar</button>
                                        <button type="button" class="tab-btn rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200" data-tab-target="corrected">Corrected</button>
                                        <button type="button" class="tab-btn rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200" data-tab-target="images">Generated Images</button>
                                        <button type="button" class="tab-btn rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200" data-tab-target="video">Generated Video</button>
                                        @if ($essay->analysis_text)
                                            <button type="button" class="tab-btn rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200" data-tab-target="analysis">Analysis</button>
                                        @endif
                                    </div>
                                    <div class="mt-3">
                                        <div data-tab-panel="attachments" class="hidden">
                                            @if (!empty($imagePaths))
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
                                            @else
                                                <p class="text-sm text-gray-600">No attachments.</p>
                                            @endif
                                        </div>
                                        <div data-tab-panel="images" class="hidden">
                                            <div class="flex items-center justify-between">
                                                <div class="text-xs font-semibold text-gray-600">Generated Images</div>
                                                <form class="image-refresh-form" method="POST" action="{{ route('previous-essays.images.regenerate', $essay->id) }}" data-essay-id="{{ $essay->id }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-100">
                                                        Refresh
                                                    </button>
                                                </form>
                                            </div>
                                            @if (!empty($generatedImagePaths))
                                                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                                    @foreach ($generatedImagePaths as $path)
                                                        <img class="max-h-64 w-full rounded-md border border-gray-200 object-contain" src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" alt="Generated essay image">
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-600">No generated images yet.</p>
                                            @endif
                                        </div>
                                        <div data-tab-panel="video" class="hidden" data-video-panel data-essay-id="{{ $essay->id }}">
                                            <div class="flex items-center justify-between">
                                                <div class="text-xs font-semibold text-gray-600">Generated Video</div>
                                                <form method="POST" action="{{ route('previous-essays.videos.regenerate', $essay->id) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-100">
                                                        Refresh
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="video-status mt-2">
                                                @if ($essay->generated_video_path)
                                                    <video class="mt-2 w-full rounded-md border border-gray-200" controls>
                                                        <source src="{{ \Illuminate\Support\Facades\Storage::url($essay->generated_video_path) }}" type="video/mp4">
                                                    </video>
                                                @else
                                                    @if ($essay->video_error)
                                                        <p class="text-sm text-red-600">{{ $essay->video_error }}</p>
                                                    @elseif ($essay->video_status)
                                                        <div>
                                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                                <span>{{ ucfirst($essay->video_status) }}</span>
                                                                <span>{{ $essay->video_progress ?? 0 }}%</span>
                                                            </div>
                                                            <div class="mt-2 h-2 w-full rounded-full bg-gray-100">
                                                                <div class="h-2 rounded-full bg-blue-500" style="width: {{ $essay->video_progress ?? 0 }}%;"></div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-gray-600">No generated video yet.</p>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <div data-tab-panel="original">
                                            <pre class="whitespace-pre-wrap text-sm text-gray-700">{{ $essay->ocr_text }}</pre>
                                            @if ($essay->original_writing)
                                                <div class="mt-3">
                                                    <div class="text-xs font-semibold text-gray-600">Original Writing (AI)</div>
                                                    <pre class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ $essay->original_writing }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                        <div data-tab-panel="spelling" class="hidden">
                                            <pre class="whitespace-pre-wrap text-sm text-gray-700">{{ $essay->spelling_mistakes }}</pre>
                                        </div>
                                        <div data-tab-panel="grammar" class="hidden">
                                            <pre class="whitespace-pre-wrap text-sm text-gray-700">{{ $essay->grammar_mistakes }}</pre>
                                        </div>
                                        <div data-tab-panel="corrected" class="hidden">
                                            <pre class="whitespace-pre-wrap text-sm text-gray-700">{{ $essay->corrected_version }}</pre>
                                        </div>
                                        @if ($essay->analysis_text)
                                            <div data-tab-panel="analysis" class="hidden">
                                                <pre class="whitespace-pre-wrap text-sm text-gray-700">{{ $essay->analysis_text }}</pre>
                                            </div>
                                        @endif
                                    </div>
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
        <script>
            (function () {
                const poll = () => {
                    const panels = Array.from(document.querySelectorAll('[data-video-panel]'));
                    if (!panels.length) return;
                    const ids = panels.map((panel) => panel.dataset.essayId).filter(Boolean);
                    if (!ids.length) return;

                    fetch("{{ route('previous-essays.video-status') }}?essay_ids=" + ids.join(','), {
                        headers: { 'Accept': 'application/json' },
                    })
                        .then((response) => response.ok ? response.json() : null)
                        .then((data) => {
                            if (!data) return;
                            panels.forEach((panel) => {
                                const essayId = panel.dataset.essayId;
                                const status = data[essayId];
                                if (!status) return;

                                const container = panel.querySelector('.video-status');
                                if (!container) return;

                                if (status.path || status.url) {
                                    const url = status.path
                                        ? ("{{ \Illuminate\Support\Facades\Storage::url('') }}" + status.path)
                                        : status.url;
                                    container.innerHTML = `
                                        <video class="mt-2 w-full rounded-md border border-gray-200" controls>
                                            <source src="${url}" type="video/mp4">
                                        </video>
                                    `;
                                    return;
                                }

                                if (status.error) {
                                    container.innerHTML = `<p class="text-sm text-red-600">${status.error}</p>`;
                                    return;
                                }

                                if (status.status) {
                                    const progress = status.progress ?? 0;
                                    const label = status.status.charAt(0).toUpperCase() + status.status.slice(1);
                                    container.innerHTML = `
                                        <div>
                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                <span>${label}</span>
                                                <span>${progress}%</span>
                                            </div>
                                            <div class="mt-2 h-2 w-full rounded-full bg-gray-100">
                                                <div class="h-2 rounded-full bg-blue-500" style="width: ${progress}%;"></div>
                                            </div>
                                        </div>
                                    `;
                                }
                            });
                        })
                        .catch(() => {});
                };

                poll();
                setInterval(poll, 5000);
            })();
        </script>
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

        const tabGroups = document.querySelectorAll('[data-tab-group]');
        tabGroups.forEach((group) => {
            const buttons = group.querySelectorAll('.tab-btn');
            const panels = group.querySelectorAll('[data-tab-panel]');
            const groupId = group.getAttribute('data-tab-group');

            const activate = (target) => {
                buttons.forEach((btn) => {
                    const isActive = btn.getAttribute('data-tab-target') === target;
                    btn.classList.toggle('bg-gray-900', isActive);
                    btn.classList.toggle('text-white', isActive);
                    btn.classList.toggle('bg-gray-100', !isActive);
                    btn.classList.toggle('text-gray-700', !isActive);
                });
                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== target);
                });

                if (groupId) {
                    sessionStorage.setItem(`essay-tab-${groupId}`, target);
                }
            };

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => activate(btn.getAttribute('data-tab-target')));
            });

            if (buttons.length) {
                const stored = groupId ? sessionStorage.getItem(`essay-tab-${groupId}`) : null;
                if (stored && Array.from(buttons).some((btn) => btn.getAttribute('data-tab-target') === stored)) {
                    activate(stored);
                } else {
                    const defaultButton = Array.from(buttons).find((btn) => btn.getAttribute('data-tab-target') === 'original');
                    activate((defaultButton ?? buttons[0]).getAttribute('data-tab-target'));
                }
            }
        });

        const refreshForms = document.querySelectorAll('.image-refresh-form');
        const imageModal = document.getElementById('image-refresh-modal');
        refreshForms.forEach((form) => {
            form.addEventListener('submit', () => {
                const essayId = form.getAttribute('data-essay-id');
                if (essayId) {
                    sessionStorage.setItem(`essay-tab-essay-${essayId}`, 'images');
                }
                if (imageModal) {
                    imageModal.classList.remove('hidden');
                    imageModal.classList.add('flex');
                }
            });
        });
    })();
</script>

<div id="image-refresh-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative z-10 w-full max-w-sm rounded-lg bg-white p-6 shadow-lg">
        <div class="flex items-center gap-3">
            <svg class="h-5 w-5 animate-spin text-gray-400" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 00-4 4H4z"></path>
            </svg>
            <span class="text-sm text-gray-700">Regenerating image...</span>
        </div>
    </div>
</div>
