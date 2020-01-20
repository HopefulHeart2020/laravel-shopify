<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;
use OhMyBrew\ShopifyApp\Services\IApiHelper;

/**
 * Delete webhooks for this app on the shop.
 */
class DeleteWebhooksAction
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param IApiHelper $apiHelper The API helper.
     * @param IShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(IApiHelper $apiHelper, IShopQuery $shopQuery)
    {
        $this->apiHelper = $apiHelper;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param int $shopId The shop ID.
     *
     * @return array
     */
    public function __invoke(int $shopId): array
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Set the API instance
        $this->apiHelper->setInstance($shop->api());

        // Get the webhooks
        $webhooks = $this->apiHelper->getWebhooks();

        $deleted = [];
        foreach ($webhooks as $webhook) {
            // Its a webhook in the config, delete it
            $this->api->deleteWebhook($webhook->id);

            // Keep track of what was deleted
            $deleted[] = $webhook;
        }

        return $deleted;
    }
}
