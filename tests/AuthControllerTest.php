<?php namespace OhMyBrew\ShopifyApp\Test;

use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Models\Shop;

class AuthControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        config(['shopify-app.api_class' => new ApiStub]);

        // HMAC
        $this->hmac = 'a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163';
        $this->hmacParams = [
            'hmac' => $this->hmac,
            'shop' => 'example.myshopify.com',
            'code' => '1234678',
            'timestamp' => '1337178173'
        ];
    }

    public function testLoginTest()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function testAuthRedirectsBackToLoginWhenNoShop()
    {
        $response = $this->post('/authenticate');
        
        $response->assertStatus(302);
        $this->assertEquals('http://localhost/login', $response->headers->get('location'));
    }

    public function testAuthRedirectsUserToAuthScreenWhenNoCode()
    {
        $response = $this->post('/authenticate', ['shop' => 'example.myshopify.com']);
        $response->assertSessionHas('shopify_domain');
        $response->assertViewHas('shopDomain', 'example.myshopify.com');
        $response->assertViewHas(
            'authUrl',
            'https://example.myshopify.com/admin/oauth/authorize?client_id=&scope=read_products,write_products&redirect_uri=http://localhost/authenticate'
        );
    }

    public function testAuthAcceptsShopWithCodeAndUpdatesTokenForShop()
    {
        $response = $this->call('get', '/authenticate', $this->hmacParams);

        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $this->assertEquals('12345678', $shop->shopify_token);
    }

    public function testAuthAcceptsShopWithCodeAndRedirectsToHome()
    {
        $response = $this->call('get', '/authenticate', $this->hmacParams);

        $response->assertStatus(302);
        $this->assertEquals('http://localhost', $response->headers->get('location'));
    }

    public function testAuthAcceptsShopWithCodeAndRedirectsToLoginIfRequestIsInvalid()
    {
        $params = $this->hmacParams;
        $params['hmac'] = 'makemeinvalid';

        $response = $this->call('get', '/authenticate', $params);

        $response->assertSessionHas('error');
        $response->assertStatus(302);
        $this->assertEquals('http://localhost/login', $response->headers->get('location'));
    }

    public function testAuthenticateDoesNotFiresJobsWhenNoConfigForThem()
    {
        Queue::fake();

        $this->call('get', '/authenticate', $this->hmacParams);

        Queue::assertNotPushed(WebhookInstaller::class);
        Queue::assertNotPushed(ScripttagInstaller::class);
    }

    public function testAuthenticateDoesFiresJobs()
    {
        Queue::fake();
        config(['shopify-app.webhooks' => [
            [
                'topic' => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create'
            ]
        ]]);
        config(['shopify-app.scripttags' => [
            [
                'src' => 'https://localhost/scripts/file.js'
            ]
        ]]);

        $this->call('get', '/authenticate', $this->hmacParams);

        Queue::assertPushed(WebhookInstaller::class);
        Queue::assertPushed(ScripttagInstaller::class);
    }
}
