<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\PaymentMethod;

class PaymentMethodPolicy
{
    use HandlesAuthorization;

    public function read(User $user, PaymentMethod $payment_method)
    {
        $can = $user->admin->getPermission('payment_method_read') != 'no';

        return $can;
    }

    public function readAll(User $user, PaymentMethod $item)
    {
        $can = $user->admin->getPermission('payment_method_read') == 'all';

        return $can;
    }

    public function create(User $user, PaymentMethod $payment_method)
    {
        $can = $user->admin->getPermission('payment_method_create') == 'yes';

        return false;
    }

    public function update(User $user, PaymentMethod $payment_method)
    {
        $ability = $user->admin->getPermission('payment_method_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $payment_method->admin_id);

        return $can;
    }

    public function delete(User $user, PaymentMethod $payment_method)
    {
        $ability = $user->admin->getPermission('payment_method_delete');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $payment_method->admin_id);

        return false;
    }

    public function disable(User $user, PaymentMethod $payment_method)
    {
        $ability = $user->admin->getPermission('payment_method_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $payment_method->admin_id);

        return $can && $payment_method->status != 'inactive';
    }

    public function enable(User $user, PaymentMethod $payment_method)
    {
        $ability = $user->admin->getPermission('payment_method_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $payment_method->admin_id);

        return $can && $payment_method->status != 'active';
    }
}
