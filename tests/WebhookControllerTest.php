<?php namespace OhMyBrew\ShopifyApp\Test;

use \ReflectionMethod;
use Illuminate\Support\Facades\Queue;

if (!class_exists('App\Jobs\OrdersCreateJob')) {
    require 'OrdersCreateJobStub.php';
}

class WebhookControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->headers = [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'example.myshopify.com',
            'HTTP_X_SHOPIFY_HMAC_SHA256' => '8432614ea1ce63b77959195b0e5e1e8469bfb7890e40ab51fb9c3ac26f8b050c', // Matches fixture data and API secret
        ];
    }

    public function testShouldReturn201ResponseOnSuccess()
    {
        Queue::fake();

        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [], [], [],
            $this->headers,
            file_get_contents(__DIR__.'/fixtures/webhook.json')
        );
        $response->assertStatus(201);

        Queue::assertPushed(\App\Jobs\OrdersCreateJob::class);
    }


    public function testShouldReturnErrorResponseOnFailure()
    {
        $response = $this->call(
            'post',
            '/webhook/products-create',
            [], [], [],
            $this->headers,
            file_get_contents(__DIR__.'/fixtures/webhook.json')
        );
        $response->assertStatus(500);
        $this->assertEquals('Missing webhook job: \App\Jobs\ProductsCreateJob', $response->exception->getMessage());
    }

    public function testShouldCaseTypeToClass()
    {
        $controller = new \OhMyBrew\ShopifyApp\Controllers\WebhookController;
        $method = new ReflectionMethod(\OhMyBrew\ShopifyApp\Controllers\WebhookController::class, 'getJobClassFromType');
        $method->setAccessible(true);

        $types = [
            'orders-create' => 'OrdersCreateJob',
            'super-duper-order' => 'SuperDuperOrderJob',
            'order' => 'OrderJob'
        ];

        foreach ($types as $type => $className) {
            $this->assertEquals("\\App\\Jobs\\$className", $method->invoke($controller, $type));
        }
    }

    public function testWebhookShouldRecieveData()
    {
        Queue::fake();

        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [], [], [],
            $this->headers,
            file_get_contents(__DIR__.'/fixtures/webhook.json')
        );
        $response->assertStatus(201);

        Queue::assertPushed(\App\Jobs\OrdersCreateJob::class, function ($job) {
            return $job->shopDomain === 'example.myshopify.com'
                   && $job->data instanceof \stdClass
                   && $job->data->email === 'jon@doe.ca'
            ;
        });
    }
}
