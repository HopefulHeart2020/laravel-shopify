<?php

namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    // Types of plans
    const PLAN_RECURRING = 1;
    const PLAN_ONETIME = 2;

    /**
     * Checks if this plan has a trial.
     *
     * @return boolean
     */
    public function hasTrial()
    {
        return $this->trial_days !== null && $this->trial_days > 0;
    }

    /**
     * Checks if this plan should be presented on install.
     *
     * @return boolean
     */
    public function isOnInstall()
    {
        return (bool) $this->on_install;
    }

    /**
     * Checks if the plan is a test.
     *
     * @return bool
     */
    public function isTest()
    {
        return (bool) $this->test;
    }
}
