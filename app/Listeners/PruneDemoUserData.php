<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PruneDemoUserData
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        $demoEmail = config('reading_college.demo_user_email');
        $demoName = config('reading_college.demo_user_name');

        if ($user->email !== $demoEmail && $user->name !== $demoName) {
            return;
        }

        DB::table('essay_submissions')->where('user_id', $user->id)->delete();
        Storage::disk('public')->deleteDirectory('demo-uploads');

        $user->delete();
    }
}
