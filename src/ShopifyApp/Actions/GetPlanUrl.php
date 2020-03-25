<?php

namespace Osiset\ShopifyApp\Actions;

use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Objects\Values\NullablePlanId;
use Osiset\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Services\ChargeHelper;

/**
 * Retrieve the a billing plan's URL.
 */
class GetPlanUrl
{
    /**
     * Querier for plans.
     *
     * @var IPlanQuery
     */
    protected $planQuery;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * The charge helper.
     *
     * @var ChargeHelper
     */
    protected $chargeHelper;

    /**
     * Setup.
     *
     * @param ChargeHelper $chargeHelper The charge helper.
     * @param IPlanQuery   $planQuery    The querier for the plans.
     * @param IShopQuery   $shopQuery    The querier for shops.
     *
     * @return self
     */
    public function __construct(ChargeHelper $chargeHelper, IPlanQuery $planQuery, IShopQuery $shopQuery)
    {
        $this->chargeHelper = $chargeHelper;
        $this->planQuery = $planQuery;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopId         $shopId The shop ID.
     * @param NullablePlanId $planId The plan to present.
     *
     * @return string
     */
    public function __invoke(ShopId $shopId, NullablePlanId $planId): string
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Get the plan
        $plan = $planId->isNull() ? $this->planQuery->getDefault() : $this->planQuery->getById($planId);

        $api = $shop->apiHelper()->createCharge(
            ChargeType::fromNative($plan->getType()->toNative()),
            $this->chargeHelper->details($plan, $shop)
        );

        return $api->confirmation_url;
    }
}
