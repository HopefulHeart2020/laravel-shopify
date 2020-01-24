<?php

namespace OhMyBrew\ShopifyApp\Contracts\Commands;

use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageCharge as UsageChargeTransfer;

/**
 * Reprecents commands for charges.
 */
interface Charge
{
    /**
     * Create a charge entry.
     *
     * @param ChargeTransfer $chargeObj The charge object.
     *
     * @return int
     */
    public function createCharge(ChargeTransfer $chargeObj): int;

    /**
     * Deletes a charge for a shop.
     *
     * @param ShopId   $shopId   The shop's ID.
     * @param ChargeId $chargeId The charge ID from Shopify.
     */
    public function deleteCharge(ShopId $shopId, ChargeId $chargeId): bool;

    /**
     * Create a usage charge.
     *
     * @param UsageChargeTransfer $chargeObj The usage charge object.
     *
     * @return integer
     */
    public function createUsageCharge(UsageChargeTransfer $chargeObj): int;
}
