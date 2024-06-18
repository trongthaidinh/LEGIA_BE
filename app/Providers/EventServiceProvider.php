<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ReactionAdded' => [
            'App\Listeners\ReactionAddedListener',
        ],
        'App\Events\CommentAdded' => [
            'App\Listeners\CommentAddedListener',
        ],
        'App\Events\ShareAdded' => [
            'App\Listeners\ShareAddedListener',
        ],
        'App\Events\FriendRequestSent' => [
            'App\Listeners\FriendRequestSentListener',
        ],
        'App\Events\NotificationRead' => [
            'App\Listeners\NotificationReadListener',
        ],
        'App\Events\FriendRequestAccepted' => [
            'App\Listeners\FriendRequestAcceptedListener',
        ],
        
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
