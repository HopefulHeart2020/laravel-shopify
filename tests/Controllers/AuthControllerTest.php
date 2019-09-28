<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Controllers\AuthController;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;

class AuthControllerTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();
        $this->withoutExceptionHandling();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testLoginRoute()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function testAuthRedirectsToShopifyWhenNoCode()
    {
        // Run the request
        $response = $this->post('/authenticate', ['shop' => 'example.myshopify.com']);

        // Check the view
        $response->assertViewHas('shopDomain', 'example.myshopify.com');
        $response->assertViewHas(
            'authUrl',
            'https://example.myshopify.com/admin/oauth/authorize?client_id=&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalhost%2Fauthenticate'
        );
    }

    public function testAuthAcceptsShopWithCode()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'access_token_grant',
        ]);

        // HMAC for regular tests
        $hmac = 'a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163';
        $hmacParams = [
            'hmac'      => $hmac,
            'shop'      => 'example.myshopify.com',
            'code'      => '1234678',
            'timestamp' => '1337178173',
        ];

        $response = $this->call('get', '/authenticate', $hmacParams);
        $response->assertRedirect();
    }

    public function testReturnToMethod()
    {
        // Set in AuthShop middleware
        Session::put('return_to', 'http://localhost/orders');

        $method = new ReflectionMethod(AuthController::class, 'returnTo');
        $method->setAccessible(true);

        // Test with session
        $result = $method->invoke(new AuthController());
        $this->assertEquals('http://localhost/orders', $result->headers->get('location'));

        // Re-test should have no return_to session
        $result = $method->invoke(new AuthController());
        $this->assertEquals('http://localhost', $result->headers->get('location'));
    }
}
