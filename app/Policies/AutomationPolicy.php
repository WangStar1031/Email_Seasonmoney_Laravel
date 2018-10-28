<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Automation;

class AutomationPolicy
{
    use HandlesAuthorization;
    
    public function read(User $user, Automation $automation)
    {
        return $automation->customer_id == $user->customer->id;
    }

    public function create(User $user, Automation $automation)
    {
        $customer = $user->customer;
        $max = $customer->getOption('automation_max');

        return $max > $customer->automationsCount() || $max == -1;
    }

    public function update(User $user, Automation $automation)
    {
        return !isset($automation->id) || $automation->customer_id == $user->customer->id && in_array($automation->status, [
                                                                Automation::STATUS_DRAFT,
                                                                Automation::STATUS_ACTIVE,
                                                                Automation::STATUS_INACTIVE
                                                            ]);
    }

    public function delete(User $user, Automation $automation)
    {
        return $automation->customer_id == $user->customer->id && in_array($automation->status, [
                                                                Automation::STATUS_DRAFT,
                                                                Automation::STATUS_ACTIVE,
                                                                Automation::STATUS_INACTIVE
                                                            ]);
    }
    
    public function sort(User $user, Automation $automation)
    {
        return $automation->customer_id == $user->customer->id;
    }
    
    public function confirm(User $user, Automation $automation)
    {
        return $automation->customer_id == $user->customer->id && $automation->isValid();
    }
    
    public function overview(User $user, Automation $automation)
    {
        return $automation->customer_id == $user->customer->id && $automation->isValid();
    }
    
    public function enable(User $user, Automation $automation)
    {
        return $automation->customer_id == $user->customer->id && in_array($automation->status, [
                                                                Automation::STATUS_INACTIVE
                                                            ]);
    }
    
    public function disable(User $user, Automation $automation)
    {
        return $automation->customer_id == $user->customer->id && in_array($automation->status, [
                                                                Automation::STATUS_ACTIVE
                                                            ]);
    }
}
