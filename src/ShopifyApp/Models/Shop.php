<?php

namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

class Shop extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shopify_domain',
        'shopify_token',
        'grandfathered',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The API instance.
     *
     * @var object
     */
    protected $api;

    /**
     * Creates or returns an instance of API for the shop.
     *
     * @return object
     */
    public function api()
    {
        if (!$this->api) {
            // Create new API instance
            $api = ShopifyApp::api();
            $api->setSession($this->shopify_domain, $this->shopify_token);

            $this->api = $api;
        }

        // Return existing instance
        return $this->api;
    }

    /**
     * Checks if a shop has a charge ID.
     *
     * @return bool
     */
    public function isPaid()
    {
        return !is_null($this->charge_id);
    }

    /**
     * Checks is shop is grandfathered in.
     *
     * @return bool
     */
    public function isGrandfathered()
    {
        return ((bool) $this->grandfathered) === true;
    }
}
