<?php

namespace OhMyBrew\ShopifyApp\Contracts;

use OhMyBrew\BasicShopifyAPI;
use Illuminate\Contracts\Auth\Authenticatable;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Values\NullablePlanId;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Storage\Models\Charge as ChargeModel;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;

/**
 * Reprecents the shop model.
 */
interface ShopModel extends Authenticatable
{
    /**
     * Get shop ID as a value object.
     *
     * @return ShopId
     */
    public function getId(): ShopId;

    /**
     * Get shop domain as a value object.
     *
     * @return ShopDomainValue;
     */
    public function getDomain(): ShopDomainValue;

    /**
     * Get shop access token as a value object.
     *
     * @return AccessTokenValue
     */
    public function getToken(): AccessTokenValue;

    /**
     * Checks is shop is grandfathered in.
     *
     * @return bool
     */
    public function isGrandfathered(): bool;

    /**
     * Checks if the shop is freemium.
     *
     * @return bool
     */
    public function isFreemium(): bool;

    /**
     * Checks if the access token is filled.
     *
     * @return bool
     */
    public function hasOfflineAccess(): bool;

    /**
     * Get the API instance for a shop.
     * TODO: Find a better way than using resolve(). However, we can't inject in model constructors.
     *
     * @param bool $returnInstance Return the API helper instance of API instance.
     *
     * @return BasicShopifyAPI|IApiHelper
     */
    public function api(bool $returnInstance = false);

    /**
     * Gets the last single or recurring charge for the shop.
     *
     * @param NullablePlanId|null $planId The plan ID to check with.
     *
     * @return ChargeModel
     */
    public function planCharge(NullablePlanId $planId = null): ?ChargeModel;
}
