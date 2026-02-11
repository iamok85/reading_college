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
                                            <div class="text-xs text-gray-500">Age {{ $child->age }} · {{ ucfirst(str_replace('-', ' ', $child->gender)) }}</div>
                                        </div>
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
                                    <label for="child_age_profile" class="block text-sm font-medium text-gray-700">Age</label>
                                    <input id="child_age_profile" name="child_age" type="number" min="1" max="18" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
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
