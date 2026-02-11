<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Child;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use App\Support\Recaptcha;
use Illuminate\Validation\ValidationException;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'child_name' => ['required', 'string', 'max:255'],
            'child_age' => ['required', 'integer', 'min:1', 'max:18'],
            'child_gender' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'recaptcha_token' => ['required', 'string'],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        if (! Recaptcha::verify($input['recaptcha_token'] ?? null, 'register')) {
            throw ValidationException::withMessages([
                'recaptcha_token' => ['reCAPTCHA verification failed.'],
            ]);
        }

        $user = User::create([
            'name' => $input['name'],
            'child_name' => $input['child_name'],
            'child_age' => $input['child_age'],
            'child_gender' => $input['child_gender'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        Child::create([
            'user_id' => $user->id,
            'name' => $input['child_name'],
            'age' => $input['child_age'],
            'gender' => $input['child_gender'],
        ]);

        return $user;
    }
}
