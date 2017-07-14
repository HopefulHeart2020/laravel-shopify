<?php namespace OhMyBrew\ShopifyApp\Test;

use \ReflectionMethod;
use Illuminate\Support\Facades\Queue;

require 'OrdersCreateJobStub.php';

class WebhookControllerTest extends TestCase
{
    public function testShouldReturn201ResponseOnSuccess()
    {
        Queue::fake();

        $response = $this->call('post', '/webhook/orders-create');
        $response->assertStatus(201);

        Queue::assertPushed(\App\Jobs\OrdersCreateJob::class);
    }


    public function testShouldReturnErrorResponseOnFailure()
    {
        $response = $this->call('post', '/webhook/products-create');
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
}
