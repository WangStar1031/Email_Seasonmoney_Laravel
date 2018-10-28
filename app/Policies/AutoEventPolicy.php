<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\AutoEvent;

class AutoEventPolicy
{
    use HandlesAuthorization;
    
    public function disable(User $user, AutoEvent $event)
    {
        return $event->automation->customer_id == $user->customer->id && in_array($event->status, [
                                                                AutoEvent::STATUS_ACTIVE
                                                            ]);
    }
    
    public function enable(User $user, AutoEvent $event)
    {
        return $event->automation->customer_id == $user->customer->id && in_array($event->status, [
                                                                AutoEvent::STATUS_INACTIVE
                                                            ]);
    }
    
    public function update(User $user, AutoEvent $event)
    {
        return $event->automation->customer_id == $user->customer->id;
    }
    
    public function moveUp(User $user, AutoEvent $event)
    {
        return $event->automation->customer_id == $user->customer->id && $event->previous_event_id != $event->automation->getInitEvent()->id;
    }
    
    public function moveDown(User $user, AutoEvent $event)
    {
        return $event->automation->customer_id == $user->customer->id && isset($event->nextEvent);
    }
}
