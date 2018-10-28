<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Subscription;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    public function readAll(User $user, Subscription $subscription, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $user->admin->getPermission('subscription_read') == 'all';
                break;
            case 'customer':
                $can = false;
                break;
        }

        return $can;
    }

    public function read(User $user, Subscription $subscription, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $user->admin->getPermission('subscription_read') != 'no';
                break;
            case 'customer':
                $can = !$subscription->id || $user->customer->id == $subscription->customer_id;
                break;
        }

        return $can;
    }

    public function create(User $user, Subscription $item, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $user->admin->getPermission('subscription_create') == 'yes';
                break;
            case 'customer':
                $current_subscription = $user->customer->getCurrentSubscription();
                $can = ($current_subscription && !$user->customer->getNextSubscription()) ||
                    $user->customer->getNotOutdatedSubscriptions()->count() == 0;
                $can = $can && (!$current_subscription || !$current_subscription->isTimeUnlimited());
                break;
        }

        return $can;
    }

    public function update(User $user, Subscription $subscription, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('subscription_update');
                $can = $ability == 'all'
                    || ($ability == 'own' && $user->admin->id == $subscription->customer->admin_id);
                break;
            case 'customer':
                $can = false;
                break;
        }

        return $can;
    }

    public function delete(User $user, Subscription $subscription, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('subscription_delete');
                $can = $ability == 'all'
                    || ($ability == 'own' && $user->admin->id == $subscription->customer->admin_id);
                break;
            case 'customer':
                $can = $user->customer->id == $subscription->customer_id &&
                    $subscription->status == Subscription::STATUS_INACTIVE;
                break;
        }

        return $can;
    }

    public function disable(User $user, Subscription $subscription, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('subscription_disable');
                $can = $ability == 'all'
                    || ($ability == 'own' && $user->admin->id == $subscription->customer->admin_id);
                break;
            case 'customer':
                $can = $user->customer->id == $subscription->customer_id;
                break;
        }

        return $can && $subscription->status == Subscription::STATUS_ACTIVE && !$subscription->isOld();
    }

    public function enable(User $user, Subscription $subscription, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('subscription_enable');
                $can = $ability == 'all'
                    || ($ability == 'own' && $user->admin->id == $subscription->customer->admin_id);
                break;
            case 'customer':
                $can = false;
                break;
        }

        return $can && in_array($subscription->status, [Subscription::STATUS_INACTIVE, Subscription::STATUS_DISABLED]) && !$subscription->isOld();
    }

    public function pay(User $user, Subscription $subscription, $role)
    {
        switch ($role) {
            case 'admin':
                $can = false;
                break;
            case 'customer':
                $can = !$subscription->isPaid();
                break;
        }

        return $can && $subscription->paid == false && !$subscription->isOld();
    }

    public function paid(User $user, Subscription $subscription, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('subscription_paid');
                $can = $ability == 'all'
                    || ($ability == 'own' && $user->admin->id == $subscription->customer->admin_id);
                break;
            case 'customer':
                $can = $user->customer->id == $subscription->customer_id;
                break;
        }

        return $can && $subscription->paid == false && !$subscription->isOld();
    }

    public function unpaid(User $user, Subscription $subscription, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('subscription_unpaid');
                $can = $ability == 'all'
                    || ($ability == 'own' && $user->admin->id == $subscription->customer->admin_id);
                break;
            case 'customer':
                $can = $user->customer->id == $subscription->customer_id;
                break;
        }

        return $can && $subscription->paid == true && !$subscription->isOld();
    }
}
