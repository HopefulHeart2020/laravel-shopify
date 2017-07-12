<?php namespace OhMyBrew\ShopifyApp\Test;

class HomeControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        config(['shopify-app.api_class' => new ApiStub]);
    }

    public function testNoShopSessionShouldRedirectToAuthenticate()
    {
        $response = $this->call('get', '/', ['shop' => 'example.myshopify.com']);
        $this->assertEquals(true, strpos($response->content(), 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testWithMismatchedShopsShouldRedirectToAuthenticate()
    {
        session(['shopify_domain' => 'example.myshopify.com']);
        $response = $this->call('get', '/', ['shop' => 'example-different-shop.myshopify.com']);
        $this->assertEquals(true, strpos($response->content(), 'Redirecting to http://localhost/authenticate') !== false);
    }


    public function testShopWithSessionShouldLoad()
    {
        session(['shopify_domain' => 'example.myshopify.com']);
        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertEquals(true, strpos($response->content(), "apiKey: ''") !== false);
        $this->assertEquals(true, strpos($response->content(), "shopOrigin: 'https://example.myshopify.com'") !== false);
    }
}
