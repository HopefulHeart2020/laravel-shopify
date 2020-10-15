<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Test\TestCase;

class HomeControllerTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Services\ShopSession
     */
    protected $shopSession;

    public function setUp(): void
    {
        parent::setUp();

        $this->shopSession = $this->app->make(ShopSession::class);
    }

    public function testHomeRouteWithAppBridge(): void
    {
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        $this->get('/')
            ->assertOk()
            ->assertSee("apiKey: '".env('SHOPIFY_API_KEY')."'", false)
            ->assertSee("shopOrigin: '{$shop->name}'", false);
    }

    public function testHomeRouteWithNoAppBridge(): void
    {
        $shop = factory($this->model)->create();
        $this->shopSession->make($shop->getDomain());

        $this->app['config']->set('shopify-app.appbridge_enabled', false);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('@shopify');
    }
}
