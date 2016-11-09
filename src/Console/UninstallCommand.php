<?php

namespace LaravelHunt\Console;

class UninstallCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hunt:uninstall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the Elasticsearch index.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $params = [
            'index' => $this->hunter->getIndexName(),
        ];

        // Check for index
        if ($this->hunter->client()->indices()->exists($params) === false) {
            $this->comment('Index does not exists');
            return;
        }

        if ($this->confirm("Are you sure you want to delete index \"{$params['index']}\"")) {
            $this->hunter->client()->indices()->delete($params);
            $this->info('Index removed');
        }
    }
}
