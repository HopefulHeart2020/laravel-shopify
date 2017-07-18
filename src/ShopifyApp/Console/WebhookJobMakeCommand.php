<?php namespace OhMyBrew\ShopifyApp\Console;

use Illuminate\Support\Str;
use Illuminate\Foundation\Console\JobMakeCommand;
use Symfony\Component\Console\Input\InputArgument;

class WebhookJobMakeCommand extends JobMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'shopify-app:make:webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new webhook job class';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/webhook-job.stub';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
            ['topic', InputArgument::REQUIRED, 'The event/topic for the job (orders/create, products/update, etc)'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function fire()
    {
        // Fire parent
        parent::fire();

        // Remind user to enter job into config
        $this->info("Don't forget to register the webhook in config/shopify-app.php. Example:");
        $this->info("
    'webhooks' => [
        [
            'topic' => '{$this->argument('topic')}',
            'address' => 'https://your-domain.com/webhook/{$this->getUrlFromName()}'
        ]
    ]
        ");
    }

    /**
     * Converts the job class name into a URL endpoint
     *
     * @return string
     */
    protected function getUrlFromName()
    {
        $name = $this->getNameInput();
        if (Str::endsWith($name, 'Job')) {
            $name = substr($name, 0, -3);
        }

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
    }
}
