<?php

namespace LaravelHunt\Console;

use Illuminate\Database\Eloquent\Model;

class FlushCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hunt:flush
                                {model : Name or comma separated names of the model(s) to index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all of the model\'s records from the index.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->getModelArgument() as $model) {
            if ($model = $this->validateModel($model)) {
                $this->flush($model);
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
    protected function flush($model)
    {
        // Get model instance
        $instance = new $model();

        if ($this->hunter->typeExists($instance) === false) {
            $this->error("Type [{$model}] does not exists.");

            return false;
        }

        $this->comment("Flushing [{$model}]");

        // Import models in chunks
        $instance->chunk(100, function ($models) {
            $this->hunter->remove($models);
        });
    }
}
