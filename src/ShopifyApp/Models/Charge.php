<?php

namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use Carbon\Carbon;

class Charge extends Model
{
    use SoftDeletes;

    // Types of charges
    const CHARGE_RECURRING = 1;
    const CHARGE_ONETIME = 2;
    const CHARGE_USAGE = 3;
    const CHARGE_CREDIT = 4;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Scope for latest charge for a shop.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc')->first();
    }

    /**
     * Scope for latest charge by type for a shop.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param integer                               $type The type of charge
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestByType($query, int $type)
    {
        return $query->where('type', $type)->orderBy('created_at', 'desc')->first();
    }

    /**
     * Gets the shop for the charge.
     *
     * @return OhMyBrew\ShopifyApp\Models\Shop
     */
    public function shop()
    {
        return $this->belongsTo('OhMyBrew\ShopifyApp\Models\Shop');
    }

    /**
     * Checks if the charge is a test.
     *
     * @return bool
     */
    public function isTest()
    {
        return (bool) $this->test;
    }

    /**
     * Checks if the charge is a type.
     *
     * @param integer $type The charge type. 
     *
     * @return bool
     */
    public function isType(int $type)
    {
        return (int) $this->type === $type;
    }

    /**
     * Checks if the charge is a trial-type charge.
     *
     * @return bool
     */
    public function isTrial()
    {
        return !is_null($this->trial_ends_on);
    }

    /**
     * Checks if the charge is currently in trial.
     *
     * @return bool
     */
    public function isActiveTrial()
    {
        return $this->isTrial() && Carbon::now()->lte(Carbon::parse($this->trial_ends_on));
    }

    /**
     * Returns the remaining trial days.
     *
     * @return integer
     */
    public function remainingTrialDays()
    {
        if (!$this->isTrial()) {
            return null;
        }

        return $this->isActiveTrial() ? Carbon::now()->diffInDays($this->trial_ends_on) : 0;
    }

    /**
     * Returns the used trial days.
     *
     * @return integer|null
     */
    public function usedTrialDays()
    {
        if (!$this->isTrial()) {
            return null;
        }

        return $this->trial_days - $this->remainingTrialDays();
    }

    /**
     * Checks if the charge was accepted (for one-time and reccuring).
     *
     * @return bool
     */
    public function wasAccepted()
    {
        return $this->status === 'accepted';
    }

    /**
     * Checks if the charge was declined (for one-time and reccuring).
     *
     * @return bool
     */
    public function wasDeclined()
    {
        return $this->status === 'declined';
    }
}
