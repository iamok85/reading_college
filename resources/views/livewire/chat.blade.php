<div>
    <div class="max-w-2xl mx-auto">

        <!-- Welcome + Chat Box -->
        <div class="text-center pt-6">
            <p class="text-lg pb-4">
                Enpower your expressions with wings of creativity.
            </p>
        </div>

        <!-- Chat Box -->
        <form wire:submit.prevent="chat" class="py-3" data-chat-form>
            <label for="chat" class="sr-only">Your message</label>
            <div class="flex flex-col gap-3 py-2 px-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex flex-1 items-center gap-3 min-w-[12rem]">
                        @if (! empty($images) || ! empty($pdfs))
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach ($images as $index => $image)
                                    <div wire:key="image-chip-{{ $index }}" class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        <img class="h-6 w-6 rounded object-cover" src="{{ $image->temporaryUrl() }}" alt="Selected image" />
                                        <span>Image attached</span>
                                    </div>
                                @endforeach
                                @foreach ($pdfs as $index => $pdf)
                                    <div wire:key="pdf-chip-{{ $index }}" class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-green-100 text-[10px] font-semibold text-green-700">PDF</span>
                                        <span class="text-green-700">{{ $pdf->getClientOriginalName() }}</span>
                                    </div>
                                @endforeach
                                <button
                                    type="button"
                                    wire:click="clearAttachments"
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-500 hover:bg-gray-200 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-gray-600 dark:hover:text-gray-100"
                                    aria-label="Remove attachment(s)"
                                    @disabled($ocrLoading || $thinking)
                                >
                                    &times;
                                </button>
                            </div>
                        @else
                            <textarea
                                wire:model.defer="input"
                                id="chat-editable"
                                rows="1"
                                class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-black focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-black dark:focus:border-blue-500 dark:focus:ring-blue-500 resize-none overflow-hidden"
                                placeholder="{{ $ocrPreview ?: 'Type your essay here or attach photos or a PDF before submission' }}"
                                @disabled($ocrLoading || $thinking)
                                data-autoresize
                            ></textarea>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <label
                            wire:loading.class="pointer-events-none opacity-50"
                            wire:target="queuedFiles,chat"
                            @class([
                            'group relative inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm',
                            'border-gray-300 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-600' => true,
                            'pointer-events-none opacity-50' => ($ocrLoading || $thinking),
                        ])>
                            <input
                                type="file"
                                wire:model="queuedFiles"
                                accept="image/*,application/pdf"
                                class="hidden"
                                @disabled($ocrLoading || $thinking)
                                wire:loading.attr="disabled"
                                wire:target="queuedFiles,chat"
                            />
                            <span>Attach file</span>
                            <span class="pointer-events-none absolute right-0 top-full z-10 mt-2 w-56 translate-y-1 rounded-md border border-gray-200 bg-white px-2 py-1 text-[11px] text-gray-600 opacity-0 shadow-sm transition-opacity group-hover:opacity-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                One or multiple photos or a PDF can be attached.
                            </span>
                        </label>
                        <button
                            type="button"
                            wire:click="clearInput"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-600"
                            @disabled($ocrLoading || $thinking)
                        >
                            Clear
                        </button>
                        <button
                            type="submit"
                            class="inline-flex justify-center p-2 text-blue-600 rounded-full cursor-pointer hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-blue-500 dark:hover:bg-gray-600"
                            wire:loading.attr="disabled"
                            wire:target="queuedFiles,chat"
                            @disabled($ocrLoading || $thinking)
                        >
                            <svg class="w-6 h-6 rotate-90" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                        </button>
                    </div>
                </div>
                @if (! empty($images) || ! empty($pdfs))
                    <textarea
                        wire:model.defer="input"
                        id="chat-editable"
                        rows="1"
                        class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-black focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-black dark:focus:border-blue-500 dark:focus:ring-blue-500 resize-none overflow-hidden"
                        placeholder="{{ $ocrPreview ?: 'Type your essay here or attach photos or a PDF before submission' }}"
                        @disabled($ocrLoading || $thinking)
                        data-autoresize
                    ></textarea>
                @endif
            </div>
            <textarea
                wire:model.defer="input"
                id="chat-hidden"
                class="hidden"
                @disabled($ocrLoading || $thinking)
            >{{ $input }}</textarea>
            @error('queuedFiles')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('images')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('input')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @if ($ocrLoading)
                <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                    <svg class="h-4 w-4 animate-spin text-gray-400" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>Processing image…</span>
                </div>
            @endif
            <div wire:loading wire:target="queuedFiles" class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                <svg class="h-4 w-4 animate-spin text-gray-400" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>Processing attachment…</span>
            </div>
            <div class="mt-4 flex justify-end">
                <button
                    type="button"
                    wire:click="downloadPdf"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-600"
                    @disabled(!$lastResponse || $ocrLoading || $thinking)
                >
                    Save as PDF
                </button>
            </div>
            <div wire:loading wire:target="chat" class="mt-3 flex items-center justify-center gap-3 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 shadow-sm">
                <svg class="h-5 w-5 animate-spin text-blue-500" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>Waiting for response…</span>
            </div>
            @if ($thinking)
                <div class="mt-3 flex items-center justify-center gap-3 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 shadow-sm">
                    <svg class="h-5 w-5 animate-spin text-blue-500" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>Waiting for response…</span>
                </div>
            @endif
        </form>

        <!-- Scrollable Content -->
        <div class="mt-6">
            @if (! empty($images))
                <div class="mb-6 rounded-md border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-800">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Image preview</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @foreach ($images as $index => $image)
                            <img wire:key="image-preview-{{ $index }}" class="max-h-64 w-full rounded-md border border-gray-200 object-contain dark:border-gray-700" src="{{ $image->temporaryUrl() }}" alt="Selected image preview" />
                        @endforeach
                    </div>
                </div>
            @endif
            @foreach($messages as $message)
                <!-- User -->
                @if($message['who'] === 'user')
                    <div class="bg-purple-800 text-white rounded p-4 my-12">
                            {!! \Illuminate\Support\Str::markdown($message['content']) !!}
                    </div>
                @endif

                <!--  LLM -->
                @if($message['who'] === 'ai')
                    <div class="my-12">
                            {!! \Illuminate\Support\Str::markdown($message['content']) !!}
                    </div>
                @endif
            @endforeach

            @if($thinking)
                <div class="my-12" wire:stream="response">
                </div>
                <img src="{{asset('/images/thinking.gif')}}" width="50" alt=""/>
            @endif
        <script>
            (function () {
                const resize = (el) => {
                    if (!el) return;
                    el.style.height = 'auto';
                    el.style.height = el.scrollHeight + 'px';
                };

                const bindAll = () => {
                    const textareas = document.querySelectorAll('textarea[data-autoresize]');
                    textareas.forEach((el) => {
                        if (!el.dataset.bound) {
                            const handler = () => resize(el);
                            el.addEventListener('input', handler);
                            el.addEventListener('change', handler);
                            el.dataset.bound = 'true';
                        }
                        resize(el);
                    });
                };

                const schedule = () => requestAnimationFrame(bindAll);

                schedule();
                document.addEventListener('livewire:load', schedule);
                document.addEventListener('livewire:update', schedule);

                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('message.processed', schedule);
                }
            })();
        </script>


    </div>
</div>
