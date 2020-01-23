<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use OhMyBrew\ShopifyApp\DTO\ShopSetPlanDTO;

/**
 * Reprecents commands for shops.
 */
interface IShopCommand
{
    /**
     * Sets a plan to a shop, meanwhile cancelling freemium.
     *
     * @param int $shopId The shop's ID.
     * @param int $planId The plan's ID.
     *
     * @return bool
     */
    public function setToPlan(int $shopId, int $planId): bool;

    /**
     * Sets the access token (offline) from Shopify to the shop.
     *
     * @param int    $shopId The shop's ID.
     * @param string $token  The token from Shopify Oauth.
     *
     * @return bool
     */
    public function setAccessToken(int $shopId, string $token): bool;

    /**
     * Cleans the shop's properties (token, plan).
     * Used for uninstalls.
     *
     * @param int $shopId The shop's ID.
     *
     * @return bool
     */
    public function clean(int $shopId): bool;

    /**
     * Soft deletes a shop.
     * Used for uninstalls.
     *
     * @param int $shopId The shop's ID.
     *
     * @return bool
     */
    public function softDelete(int $shopId): bool;

    /**
     * Restore a soft-deleted shop.
     *
     * @param int $shopId The shop's ID.
     *
     * @return bool
     */
    public function restore(int $shopId): bool;
}
