<?php

namespace Acelle\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Acelle\Library\Notification\CronJob;
use Acelle\Library\Notification\SystemUrl;
use Acelle\Events\AdminLoggedIn;

class AdminLoggedInListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AdminLoggedIn  $event
     * @return void
     */
    public function handle(AdminLoggedIn $event)
    {
        // Check CronJob
        CronJob::check();
        
        // Check System URL
        SystemUrl::check();
    }
}
