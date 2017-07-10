<?php namespace OhMyBrew\ShopifyApp\Test;

use \ReflectionObject;
use \ReflectionMethod;
use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Models\Shop;

class WebhookInstallerJobTest extends TestCase
{
    public function setup()
    {
        parent::setup();

        $this->shop = Shop::find(1);
        $this->webhooks = [
            [
                'topic' => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create'
            ]
        ];
    }

    public function testJobAcceptsLoad()
    {
        $job = new WebhookInstaller($this->shop, $this->webhooks);

        $refJob = new ReflectionObject($job);
        $refWebhooks = $refJob->getProperty('webhooks');
        $refWebhooks->setAccessible(true);
        $refShop = $refJob->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($this->webhooks, $refWebhooks->getValue($job));
        $this->assertEquals($this->shop, $refShop->getValue($job));
    }

    public function testJobShouldTestWebhookExistanceMethod()
    {
        config(['shopify-app.api_class' => new ApiStub]);
        $job = new WebhookInstaller($this->shop, $this->webhooks);

        $method = new ReflectionMethod($job, 'webhookExists');
        $method->setAccessible(true);

        $result = $method->invoke(
            $job,
            [
                (object) ['address' => 'http://localhost/webhooks/test']
            ],
            [
                'address' => 'http://localhost/webhooks/test'
            ]
        );
        $result_2 = $method->invoke(
            $job,
            [
                (object) ['address' => 'http://localhost/webhooks/test']
            ],
            [
                'address' => 'http://localhost/webhooks/test-two'
            ]
        );

        $this->assertTrue($result);
        $this->assertFalse($result_2);
    }

    public function testJobShouldNotRecreateWebhooks()
    {
        // Replace with our API
        config(['shopify-app.api_class' => new ApiStub]);
        $job = new WebhookInstaller($this->shop, $this->webhooks);
        $created = $job->handle();

        // Webhook JSON comes from fixture JSON which matches $this->webhooks
        // so this should be 0
        $this->assertEquals(0, sizeof($created));
    }

    public function testJobShouldCreateWebhooks()
    {
        $webhooks = [
            [
                'topic' => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create-two'
            ]
        ];

        // Replace with our API
        config(['shopify-app.api_class' => new ApiStub]);
        $job = new WebhookInstaller($this->shop, $webhooks);
        $created = $job->handle();

        // $webhooks is new webhooks which does not exist in the JSON fixture
        // for webhooks, so it should create it
        $this->assertEquals(1, sizeof($created));
        $this->assertEquals($webhooks[0], $created[0]);
    }
}
