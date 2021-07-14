<?php

namespace Osiset\ShopifyApp\Objects\Transfers;

use Osiset\ShopifyApp\Objects\Values\ChargeReference;

/**
 * Represents details for a usage charge.
 */
final class UsageChargeDetails extends AbstractTransfer
{
    /**
     * The Shopify charge ID.
     *
     * @var ChargeReference
     */
    public $chargeReference;

    /**
     * Usage charge price.
     *
     * @var float
     */
    public $price;

    /**
     * Usage charge description.
     *
     * @var string
     */
    public $description;
}
