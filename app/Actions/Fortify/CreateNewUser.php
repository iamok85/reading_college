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
        $minYear = now()->year - 18;
        $maxYear = now()->year - 1;

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'child_name' => ['required', 'string', 'max:255'],
            'child_birth_year' => ['required', 'integer', 'min:' . $minYear, 'max:' . $maxYear],
            'child_gender' => ['required', 'string', 'max:50'],
            'plan_type' => ['required', 'string', 'in:free,sliver,gold,premium'],
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

        $birthYear = (int) $input['child_birth_year'];
        $age = now()->year - $birthYear;

        $planType = $input['plan_type'] ?? 'free';
        $isPaidPlan = in_array($planType, ['sliver', 'gold', 'premium'], true);

        $user = User::create([
            'name' => $input['name'],
            'child_name' => $input['child_name'],
            'child_birth_year' => $input['child_birth_year'],
            'child_gender' => $input['child_gender'],
            'child_age' => $age,
            'plan_type' => $planType,
            'free_trial_used_at' => $isPaidPlan ? now() : null,
            'free_trial_ends_at' => $isPaidPlan ? now()->addMonth() : null,
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        Child::create([
            'user_id' => $user->id,
            'name' => $input['child_name'],
            'age' => $age,
            'birth_year' => $input['child_birth_year'],
            'gender' => $input['child_gender'],
        ]);

        return $user;
    }
}
