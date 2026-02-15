<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\EssaySubmission;

class PruneDemoUserData
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        $email = $user->email ?? '';
        $demoEmail = config('reading_college.demo_user_email');
        $isDemo = $email === $demoEmail || Str::startsWith($email, 'demo+');

        if (! $isDemo) {
            return;
        }

        EssaySubmission::where('user_id', $user->id)->delete();
        Storage::disk('public')->deleteDirectory('demo-uploads/' . $user->id);

        $user->delete();
    }
}
