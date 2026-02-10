<?php

namespace App\Providers;

use App\Listeners\PruneDemoUserData;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Logout::class => [
            PruneDemoUserData::class,
        ],
    ];
}
