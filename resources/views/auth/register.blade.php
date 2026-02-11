<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <input type="hidden" name="recaptcha_token" id="recaptcha_token">

            <div>
                <x-label for="name" value="{{ __('Name') }}" />
                <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            </div>

            <div class="mt-4">
                <x-label for="child_name" value="{{ __('Child\\'s Name') }}" />
                <x-input id="child_name" class="block mt-1 w-full" type="text" name="child_name" :value="old('child_name')" required autocomplete="given-name" />
            </div>

            <div class="mt-4">
                <x-label for="child_age" value="{{ __('Child\\'s Age') }}" />
                <x-input id="child_age" class="block mt-1 w-full" type="number" name="child_age" :value="old('child_age')" min="1" max="18" required autocomplete="off" />
            </div>

            <div class="mt-4">
                <x-label for="child_gender" value="{{ __('Child\\'s Gender') }}" />
                <select id="child_gender" name="child_gender" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="" disabled {{ old('child_gender') ? '' : 'selected' }}>Select</option>
                    <option value="female" {{ old('child_gender') === 'female' ? 'selected' : '' }}>Female</option>
                    <option value="male" {{ old('child_gender') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="non-binary" {{ old('child_gender') === 'non-binary' ? 'selected' : '' }}>Non-binary</option>
                    <option value="prefer-not-to-say" {{ old('child_gender') === 'prefer-not-to-say' ? 'selected' : '' }}>Prefer not to say</option>
                </select>
            </div>

            <div class="mt-4">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="mt-4">
                    <x-label for="terms">
                        <div class="flex items-center">
                            <x-checkbox name="terms" id="terms" required />

                            <div class="ms-2">
                                {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                        'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Terms of Service').'</a>',
                                        'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Privacy Policy').'</a>',
                                ]) !!}
                            </div>
                        </div>
                    </x-label>
                </div>
            @endif

            <div class="flex items-center justify-end mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-button class="ms-4">
                    {{ __('Register') }}
                </x-button>
            </div>
        </form>

        <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
        <script>
            grecaptcha.ready(function () {
                grecaptcha.execute("{{ config('services.recaptcha.site_key') }}", { action: "register" })
                    .then(function (token) {
                        var input = document.getElementById('recaptcha_token');
                        if (input) {
                            input.value = token;
                        }
                    });
            });
        </script>
    </x-authentication-card>
</x-guest-layout>
