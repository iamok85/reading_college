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
                <a href="{{ route('jobs') }}" class="hover:text-gray-900 {{ request()->routeIs('jobs') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Processing') }}
                </a>
                <a href="{{ route('previous-essays') }}" class="hover:text-gray-900 {{ request()->routeIs('previous-essays') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Previous Essays') }}
                </a>
                <a href="{{ route('analysis') }}" class="hover:text-gray-900 {{ request()->routeIs('analysis') ? 'text-gray-900 underline' : '' }}">
                    {{ __('Analysis') }}
                </a>
            @endauth
        </div>
        @if (Auth::user()->children->isNotEmpty())
            <form class="mt-4 flex items-center gap-2 text-sm" method="GET" action="{{ url()->current() }}">
                <label for="child_select_uploaded" class="text-gray-600">Child:</label>
                <select id="child_select_uploaded" name="child_id" class="min-w-[280px] rounded-md border border-gray-300 px-2 py-1 text-sm" onchange="this.form.submit()" required>
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
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h1 class="text-xl font-semibold text-gray-900">Essay Uploaded</h1>
                    <a href="{{ route('previous-essays') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        View All Essays
                    </a>
                </div>

                <div class="text-sm text-gray-600">Uploaded: {{ \Carbon\Carbon::parse($essay->uploaded_at)->format('Y-m-d H:i') }}</div>

                @if ($essay->processing_status === 'failed')
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $essay->processing_error ?: 'The background job failed.' }}
                    </div>
                @endif

                <div class="mt-6 space-y-6">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">OCR Text</h2>
                        <pre class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $essay->ocr_text }}</pre>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Correction</h2>
                        <pre class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $essay->response_text }}</pre>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Analysis</h2>
                        <pre class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $essay->analysis_text }}</pre>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Attachments</h2>
                        @if (!empty($imagePaths))
                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                @foreach ($imagePaths as $path)
                                    @if (\Illuminate\Support\Str::endsWith(\Illuminate\Support\Str::lower($path), '.pdf'))
                                        <a class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-green-700 hover:bg-gray-100" href="{{ \Illuminate\Support\Facades\Storage::url($path) }}" target="_blank" rel="noopener">
                                            View PDF
                                        </a>
                                    @else
                                        <img class="max-h-64 w-full rounded-md border border-gray-200 object-contain" src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" alt="Essay attachment">
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-sm text-gray-600">No attachments.</p>
                        @endif
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Generated Images</h2>
                        @if (!empty($generatedImagePaths))
                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                @foreach ($generatedImagePaths as $path)
                                    <img class="max-h-64 w-full rounded-md border border-gray-200 object-contain" src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" alt="Generated essay image">
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-sm text-gray-600">No generated images yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
