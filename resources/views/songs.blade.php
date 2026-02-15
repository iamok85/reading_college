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
                <label for="child_select_songs" class="text-gray-600">Child:</label>
                <select id="child_select_songs" name="child_id" class="min-w-[200px] rounded-md border border-gray-300 px-2 py-1 text-sm" onchange="this.form.submit()" required>
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
                <h2 class="text-lg font-semibold text-gray-900">Songs</h2>
                <p class="mt-2 text-sm text-gray-600">Generate a song from each corrected essay.</p>
                @if ($errors->has('song'))
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first('song') }}
                    </div>
                @endif

                @if ($essays->isEmpty())
                    <p class="mt-4 text-sm text-gray-600">No submissions yet.</p>
                @else
                    <div class="mt-6 space-y-4">
                        @foreach ($essays as $essay)
                            @php
                                $song = $songs->get($essay->id);
                            @endphp
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="text-sm text-gray-600">
                                        Uploaded: {{ \Carbon\Carbon::parse($essay->uploaded_at)->format('Y-m-d H:i') }}
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-600">
                                        <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700">
                                            {{ $song?->status ? ucfirst($song->status) : 'Not generated' }}
                                        </span>
                                        @if ($song?->song_path && $song?->status === 'ready')
                                            <a class="text-blue-700 hover:underline" href="{{ \Illuminate\Support\Facades\Storage::url($song->song_path) }}" target="_blank" rel="noopener">
                                                Download
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                @if ($song?->song_path && $song?->status === 'ready')
                                    <div class="mt-3">
                                        <div class="text-sm font-semibold text-gray-800">{{ $song->song_name ?? 'Essay Song' }}</div>
                                        <audio class="mt-2 w-full" controls>
                                            <source src="{{ \Illuminate\Support\Facades\Storage::url($song->song_path) }}" type="audio/mpeg">
                                        </audio>
                                    </div>
                                @elseif ($song?->status === 'failed')
                                    <p class="mt-3 text-sm text-red-600">Song generation failed. Please try again.</p>
                                @endif

                                <form class="mt-4" method="POST" action="{{ route('songs.generate', $essay->id) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                        Generate Song
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
