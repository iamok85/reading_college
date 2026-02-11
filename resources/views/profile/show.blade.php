<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')

                <x-section-border />
            @endif

            <div class="mt-10 sm:mt-0">
                <x-action-section>
                    <x-slot name="title">
                        {{ __('Plan') }}
                    </x-slot>

                    <x-slot name="description">
                        {{ __('Your current subscription tier.') }}
                    </x-slot>

                    <x-slot name="content">
                        <form class="flex flex-wrap items-end gap-4" method="POST" action="{{ route('profile.plan.update') }}">
                            @csrf
                            <div>
                                <label for="plan_type" class="block text-xs font-medium text-gray-600">Plan</label>
                                <select id="plan_type" name="plan_type" class="mt-1 w-40 rounded-md border border-gray-300 px-2 py-1 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="free" {{ (Auth::user()->plan_type ?? 'free') === 'free' ? 'selected' : '' }}>Free</option>
                                    <option value="sliver" {{ (Auth::user()->plan_type ?? '') === 'sliver' ? 'selected' : '' }}>Sliver</option>
                                    <option value="gold" {{ (Auth::user()->plan_type ?? '') === 'gold' ? 'selected' : '' }}>Gold</option>
                                    <option value="premium" {{ (Auth::user()->plan_type ?? '') === 'premium' ? 'selected' : '' }}>Premium</option>
                                </select>
                            </div>
                            <div id="plan-info" class="text-xs text-gray-600" data-default="{{ Auth::user()->plan_type ?? 'free' }}">
                                @php
                                    $planType = Auth::user()->plan_type ?? 'free';
                                @endphp
                                @switch($planType)
                                    @case('sliver')
                                        $10 / month · 20 submissions
                                        @break
                                    @case('gold')
                                        $20 / month · 50 submissions
                                        @break
                                    @case('premium')
                                        $30 / month · Unlimited submissions
                                        @break
                                    @default
                                        Free · 20 submissions (first month)
                                @endswitch
                            </div>
                            <button type="submit" class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800">
                                Update
                            </button>
                        </form>
                    </x-slot>
                </x-action-section>
            </div>

            <div class="mt-10 sm:mt-0">
                <x-action-section>
                    <x-slot name="title">
                        {{ __('Children') }}
                    </x-slot>

                    <x-slot name="description">
                        {{ __('Manage your child profiles for tailored recommendations.') }}
                    </x-slot>

                    <x-slot name="content">
                        @if (Auth::user()->children->isEmpty())
                            <p class="text-sm text-gray-600">No child profiles yet.</p>
                        @else
                            <div class="space-y-3">
                                @foreach (Auth::user()->children as $child)
                                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-md border border-gray-200 px-4 py-3">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-800">{{ $child->name }}</div>
                                            <div class="text-xs text-gray-500">Born {{ $child->birth_year }} · {{ ucfirst(str_replace('-', ' ', $child->gender)) }}</div>
                                        </div>
                                        <form class="child-inline-form flex flex-wrap items-end gap-3" method="POST" action="{{ route('children.update', $child) }}">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600" for="child_name_{{ $child->id }}">Name</label>
                                                <input id="child_name_{{ $child->id }}" name="child_name" type="text" value="{{ $child->name }}" class="mt-1 w-32 rounded-md border border-gray-300 px-2 py-1 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600" for="child_age_{{ $child->id }}">Birth Year</label>
                                                <input id="child_age_{{ $child->id }}" name="child_birth_year" type="number" min="{{ now()->year - 18 }}" max="{{ now()->year - 1 }}" value="{{ $child->birth_year }}" class="mt-1 w-24 rounded-md border border-gray-300 px-2 py-1 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600" for="child_gender_{{ $child->id }}">Gender</label>
                                                <select id="child_gender_{{ $child->id }}" name="child_gender" class="mt-1 w-36 rounded-md border border-gray-300 px-2 py-1 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                                    <option value="female" {{ $child->gender === 'female' ? 'selected' : '' }}>Female</option>
                                                    <option value="male" {{ $child->gender === 'male' ? 'selected' : '' }}>Male</option>
                                                    <option value="non-binary" {{ $child->gender === 'non-binary' ? 'selected' : '' }}>Non-binary</option>
                                                    <option value="prefer-not-to-say" {{ $child->gender === 'prefer-not-to-say' ? 'selected' : '' }}>Prefer not to say</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800">
                                                Update
                                            </button>
                                            <span class="child-save-status text-xs text-gray-500"></span>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form class="mt-6" method="POST" action="{{ route('children.store') }}">
                            @csrf
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-2">
                                    <label for="child_name_profile" class="block text-sm font-medium text-gray-700">Child's Name</label>
                                    <input id="child_name_profile" name="child_name" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <div class="col-span-6 sm:col-span-2">
                                    <label for="child_age_profile" class="block text-sm font-medium text-gray-700">Birth Year</label>
                                    <input id="child_age_profile" name="child_birth_year" type="number" min="{{ now()->year - 18 }}" max="{{ now()->year - 1 }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <div class="col-span-6 sm:col-span-2">
                                    <label for="child_gender_profile" class="block text-sm font-medium text-gray-700">Gender</label>
                                    <select id="child_gender_profile" name="child_gender" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="" disabled selected>Select</option>
                                        <option value="female">Female</option>
                                        <option value="male">Male</option>
                                        <option value="non-binary">Non-binary</option>
                                        <option value="prefer-not-to-say">Prefer not to say</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                    Add Child
                                </button>
                            </div>
                        </form>
                    </x-slot>
                </x-action-section>
            </div>

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.update-password-form')
                </div>

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.two-factor-authentication-form')
                </div>

                <x-section-border />
            @endif

            <div class="mt-10 sm:mt-0">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire('profile.delete-user-form')
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
<script>
    (function () {
        const forms = document.querySelectorAll('form.child-inline-form');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        forms.forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const status = form.querySelector('.child-save-status');
                if (status) {
                    status.textContent = 'Saving...';
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token || '',
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });

                    if (!response.ok) {
                        throw new Error('Save failed');
                    }

                    if (status) {
                        status.textContent = 'Saved';
                    }
                } catch (error) {
                    if (status) {
                        status.textContent = 'Error';
                    }
                }
            });
        });
    })();

    (function () {
        const select = document.getElementById('plan_type');
        const info = document.getElementById('plan-info');
        if (!select || !info) return;

        const copy = {
            free: 'Free · 20 submissions (first month)',
            sliver: '$10 / month · 20 submissions',
            gold: '$20 / month · 50 submissions',
            premium: '$30 / month · Unlimited submissions',
        };

        const update = () => {
            const value = select.value || info.dataset.default || 'free';
            info.textContent = copy[value] || copy.free;
        };

        select.addEventListener('change', update);
        update();
    })();
</script>
