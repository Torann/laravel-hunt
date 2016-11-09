<?php

namespace LaravelHunt\Console;

use Illuminate\Database\Eloquent\Model;

class ImportCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hunt:import
                                {model : Name or comma separated names of the model(s) to index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all the entries in an Eloquent model.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->getModelArgument() as $model) {
            if ($model = $this->validateModel($model)) {
                $this->index($model);
            }
        }
    }

    /**
     * Index all model entries to ElasticSearch.
     *
     * @param string $model
     *
     * @return bool
     */
    protected function index($model)
    {
        $this->comment("Processing [{$model}]");

        // Get model instance
        $instance = new $model();

        // Check for map and create if missing
        if ($this->hunter->typeExists($instance) === false) {
            $this->line(' - Mapping');
            $this->hunter->putMapping($instance);
        }

        // Index model
        $this->line(' - Importing');

        // Import models in chunks
        $instance->chunk(100, function ($models) {
            $this->hunter->update($models);
        });
    }
}
