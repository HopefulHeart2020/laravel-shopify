<?php namespace OhMyBrew\ShopifyApp\Test\Middleware;

use OhMyBrew\ShopifyApp\Middleware\AuthWebhook;
use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Test\TestCase;

if (!class_exists('App\Jobs\OrdersCreateJob')) {
    require __DIR__.'/../Stubs/OrdersCreateJobStub.php';
}

class AuthWebhookMiddlewareTest extends TestCase
{
    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Invalid webhook signature
     */
    public function testDenysForMissingShopHeader()
    {
        request()->header('x-shopify-hmac-sha256', '1234');
        (new AuthWebhook)->handle(request(), function($request) { });
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Invalid webhook signature
     */
    public function testDenysForMissingHmacHeader()
    {
        request()->header('x-shopify-shop-domain', 'example.myshopify.com');
        (new AuthWebhook)->handle(request(), function($request) { });
    }

    public function testRuns()
    {
        Queue::fake();
        
        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [], [], [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'example.myshopify.com',
                'HTTP_X_SHOPIFY_HMAC_SHA256' => '8432614ea1ce63b77959195b0e5e1e8469bfb7890e40ab51fb9c3ac26f8b050c', // Matches fixture data and API secret
            ],
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );
        $response->assertStatus(201);
    }

    public function testInvalidHmacWontRun()
    {
        Queue::fake();
        
        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [], [], [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'example.myshopify.com',
                'HTTP_X_SHOPIFY_HMAC_SHA256' => '8432614ea1ce63b77959195b0e5e1e8469bfb7890e40ab51fb9c3ac26f8b050c', // Matches fixture data and API secret
            ],
            file_get_contents(__DIR__.'/../fixtures/webhook.json') . 'invalid'
        );
        $response->assertStatus(401);
    }
}
