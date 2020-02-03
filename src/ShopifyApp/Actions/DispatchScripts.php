<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;

/**
 * Attempt to install script tags on a shop.
 */
class DispatchScripts
{
    use ConfigAccessible;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param IShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(IShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     *
     * @param ShopId $shopId The shop ID.
     * @param bool   $inline Fire the job inlin e (now) or queue.
     *
     * @return bool
     */
    public function __invoke(ShopId $shopId, bool $inline = false): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Get the scripttags
        $scripttags = $this->getConfig('scripttags');
        if (count($scripttags) === 0) {
            // Nothing to do
            return false;
        }

        // Run the installer job
        if ($inline) {
            ScripttagInstaller::dispatchNow($shop, $scripttags);
        } else {
            ScripttagInstaller::dispatch($shop, $scripttags)
                ->onQueue($this->getConfig('job_queues')['scripttags']);
        }

        return true;
    }
}
