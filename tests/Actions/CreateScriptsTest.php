<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Actions\CreateScripts;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;

class CreateScriptsTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\CreateScripts
     */
    protected $action;

    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(CreateScripts::class);
    }

    public function testShouldNotCreateIfExists(): void
    {
        // Create the config
        $scripts = [
            [
                'src' => 'https://js-aplenty.com/foo.js',
            ],
            [
                'src' => 'https://js-aplenty.com/bar.js',
            ]
        ];
        $this->app['config']->set('shopify-app.scripttags', $scripts);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_script_tags',
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $scripts
        );

        $this->assertEquals(0, count($result['created']));
        $this->assertEquals(0, count($result['deleted']));
    }

    public function testShouldCreateOnlyNewOnesAndDeleteUnusedScripts(): void
    {
        // Create the config
        $scripts = [
            [
                'src' => 'https://js-aplenty.com/some-new-script.js',
            ],
        ];
        $this->app['config']->set('shopify-app.scripttags', $scripts);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_script_tags',
            'post_script_tags',
            'post_script_tags',
            'post_script_tags',
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $scripts
        );

        $this->assertEquals(1, count($result['created']));
        $this->assertEquals(2, count($result['deleted']));

        $this->assertTrue($result['created'][0]['src'] === 'https://js-aplenty.com/some-new-script.js');
        $this->assertTrue($result['deleted'][0]['src'] === 'https://js-aplenty.com/bar.js');
        $this->assertTrue($result['deleted'][1]['src'] === 'https://js-aplenty.com/foo.js');
    }

    public function testShouldCreate(): void
    {
        // Create the config
        $scripts = [
            [
                'src' => 'https://js-aplenty.com/foo-bar.js',
            ],
            [
                'src' => 'https://js-aplenty.com/foo.js',
            ],
            [
                'src' => 'https://js-aplenty.com/bar.js',
            ]
        ];
        $this->app['config']->set('shopify-app.scripttags', $scripts);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_script_tags',
            'post_script_tags'
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $scripts
        );

        $this->assertEquals(1, count($result['created']));
        $this->assertEquals(0, count($result['deleted']));

        $this->assertTrue($result['created'][0]['src'] === 'https://js-aplenty.com/foo-bar.js');
    }
}
