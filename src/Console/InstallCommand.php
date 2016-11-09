<?php

namespace LaravelHunt\Console;

class InstallCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hunt:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the Elasticsearch index.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $params = [
            'index' => $this->hunter->getIndexName(),
        ];

        // Check for index
        if ($this->hunter->client()->indices()->exists($params)) {
            $this->comment("Index [{$params['index']}] already exists");
            return false;
        }

        // Check for default settings
        if ($settings = $this->hunter->config('settings')) {
            $params['body'] = [
                'settings' => $settings
            ];
        }

        $this->hunter->client()->indices()->create($params);

        $this->comment("Index [{$params['index']}] installed");
    }
}
