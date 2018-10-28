<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Subscriber;

class SubscriberPolicy
{
    use HandlesAuthorization;

    public function read(User $user, Subscriber $item)
    {
        $customer = $user->customer;
        return $item->mailList->customer_id == $customer->id;
    }

    public function create(User $user, Subscriber $item)
    {
        $customer = $user->customer;
        $max = $customer->getOption('subscriber_max');
        $max_per_list = $customer->getOption('subscriber_per_list_max');

        return $customer->id == $item->mailList->customer_id &&
            // @todo: performance issue here
            ($max > $customer->subscribersCount() || $max == -1) &&
            ($max_per_list > $item->mailList->subscribersCount() || $max_per_list == -1);
    }

    public function update(User $user, Subscriber $item)
    {
        $customer = $user->customer;
        return $item->mailList->customer_id == $customer->id;
    }

    public function delete(User $user, Subscriber $item)
    {
        $customer = $user->customer;
        return $item->mailList->customer_id == $customer->id;
    }

    public function subscribe(User $user, Subscriber $item)
    {
        $customer = $user->customer;
        return $item->mailList->customer_id == $customer->id && $item->status == 'unsubscribed';
    }

    public function unsubscribe(User $user, Subscriber $item)
    {
        $customer = $user->customer;
        return $item->mailList->customer_id == $customer->id && $item->status == 'subscribed';
    }
}
