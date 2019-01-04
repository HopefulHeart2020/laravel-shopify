<?php

namespace OhMyBrew\ShopifyApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OhMyBrew\ShopifyApp\Services\WebhookManager;

/**
 * Webhook job responsible for handling installation of webhook listeners.
 */
class WebhookInstaller implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The shop object.
     *
     * @var object
     */
    protected $shop;

    /**
     * Webhook manager.
     *
     * @var \OhMyBrew\ShopifyApp\Services\WebhookManager
     */
    protected $manager;

    /**
     * Create a new job instance.
     *
     * @param object $shop The shop object
     *
     * @return void
     */
    public function __construct($shop)
    {
        $this->shop = $shop;
        $this->manager = new WebhookManager($this->shop);
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle()
    {
        return $this->manager->createWebhooks();
    }
}
