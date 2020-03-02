<?php

namespace Osiset\ShopifyApp\Actions;

use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Traits\ConfigAccessible;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Objects\Values\NullAccessToken;

/**
 * Authenticates a shop via HTTP request.
 */
class AuthorizeShop
{
    use ConfigAccessible;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Commander for shops.
     *
     * @var IShopCommand
     */
    protected $shopCommand;

    /**
     * The shop session handler.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Setup.
     *
     * @param IShopQuery  $shopQuery   The querier for the shop.
     * @param ShopSession $shopSession The shop session handler.
     *
     * @return self
     */
    public function __construct(
        IShopQuery $shopQuery,
        IShopCommand $shopCommand,
        ShopSession $shopSession
    ) {
        $this->shopQuery = $shopQuery;
        $this->shopCommand = $shopCommand;
        $this->shopSession = $shopSession;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopDomain  $shopDomain The shop ID.
     * @param string|null $code       The code from Shopify.
     *
     * @return object
     */
    public function __invoke(ShopDomain $shopDomain, ?string $code): object
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain($shopDomain);
        if ($shop === null) {
            // Shop does not exist, make them and re-get
            $this->shopCommand->make($shopDomain, new NullAccessToken(null));
            $shop = $this->shopQuery->getByDomain($shopDomain);
        }
        $apiHelper = $shop->apiHelper();

        // Return data
        $return = [
            'completed' => false,
            'url'       => null,
        ];

        // Start the process
        if (empty($code)) {
            // We need the code first
            $authUrl = $apiHelper->buildAuthUrl(
                $shop->hasOfflineAccess() ?
                    AuthMode::fromNative($this->getConfig('api_grant_mode')) :
                    AuthMode::OFFLINE(),
                $this->getConfig('api_scopes')
            );

            // Call the partial callback with the shop and auth URL as params
            $return['url'] = $authUrl;
        } else {
            // We have a good code, get the access details
            $session = $this->shopSession->make($shop->getDomain());
            $session->setAccess($apiHelper->getAccessData($code));

            $return['completed'] = true;
        }

        return (object) $return;
    }
}
