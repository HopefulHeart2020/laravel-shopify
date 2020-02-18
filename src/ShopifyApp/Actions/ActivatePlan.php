<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Services\ChargeHelper;
use OhMyBrew\ShopifyApp\Objects\Enums\PlanType;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeStatus;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeReference;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use OhMyBrew\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;

/**
 * Activates a plan for a shop.
 */
class ActivatePlan
{
    /**
     * The charge helper.
     *
     * @var ChargeHelper
     */
    protected $chargeHelper;

    /**
     * Action which cancels the current plan.
     *
     * @var callable
     */
    protected $cancelCurrentPlan;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Command for charges.
     *
     * @var IChargeCommand
     */
    protected $chargeCommand;

    /**
     * Command for shops.
     *
     * @var IShopCommand
     */
    protected $shopCommand;

    /**
     * Querier for plans.
     *
     * @var IPlanQuery
     */
    protected $planQuery;

    /**
     * Setup.
     *
     * @param callable       $cancelCurrentPlanAction Action which cancels the current plan.
     * @param ChargeHelper   $chargeHelper            The charge helper.
     * @param IShopQuery     $shopQuery               The querier for shops.
     * @param IPlanQuery     $planQuery               The querier for plans.
     * @param IChargeCommand $chargeCommand           The commands for charges.
     * @param IShopCommand   $shopCommand             The commands for shops.
     *
     * @return self
     */
    public function __construct(
        callable $cancelCurrentPlanAction,
        ChargeHelper $chargeHelper,
        IShopQuery $shopQuery,
        IPlanQuery $planQuery,
        IChargeCommand $chargeCommand,
        IShopCommand $shopCommand
    ) {
        $this->cancelCurrentPlan = $cancelCurrentPlanAction;
        $this->chargeHelper = $chargeHelper;
        $this->shopQuery = $shopQuery;
        $this->planQuery = $planQuery;
        $this->chargeCommand = $chargeCommand;
        $this->shopCommand = $shopCommand;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopId          $shopId    The shop ID.
     * @param PlanId          $planId    The plan to use.
     * @param ChargeReference $chargeRef The charge ID from Shopify.
     *
     * @return ChargeId
     */
    public function __invoke(ShopId $shopId, PlanId $planId, ChargeReference $chargeRef): ChargeId
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Get the plan
        $plan = $this->planQuery->getById($planId);
        $chargeType = ChargeType::fromNative($plan->getType()->toNative());

        // Activate the plan on Shopify
        $response = $shop->apiHelper()->activateCharge($chargeType, $chargeRef);

        // Cancel the shop's current plan
        call_user_func($this->cancelCurrentPlan, $shopId);

        // Cancel the existing charge if it exists (happens if someone refreshes during)
        $this->chargeCommand->delete($chargeRef, $shopId);

        // Create the charge transfer
        $isRecurring = $plan->isType(PlanType::RECURRING());
        $transfer = new ChargeTransfer();
        $transfer->shopId = $shopId;
        $transfer->planId = $planId;
        $transfer->chargeReference = $chargeRef;
        $transfer->chargeType = $chargeType;
        $transfer->chargeStatus = ChargeStatus::fromNative(strtoupper($response->status));
        $transfer->activatedOn = $response->activated_on ? new Carbon($response->activated_on) : Carbon::today();
        $transfer->billingOn = $isRecurring ? new Carbon($response->billing_on) : null;
        $transfer->trialEndsOn = $isRecurring ? new Carbon($response->trial_ends_on) : null;
        $transfer->planDetails = $this->chargeHelper->details($plan, $shop);

        // Create the charge
        $charge = $this->chargeCommand->make($transfer);
        $this->shopCommand->setToPlan($shopId, $planId);

        return $charge;
    }
}
