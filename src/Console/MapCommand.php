<?php

namespace LaravelHunt\Console;

class MapCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hunt:map
                                {action : Mapping action to perform (add or remove)}
                                {model : Name or comma separated names of the model(s) to initialize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize a Eloquent model.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->getActionArgument(['add', 'remove']);

        foreach ($this->getModelArgument() as $model) {
            if ($model = $this->validateModel("\\App\\{$model}")) {
                $this->$action($model);
            }
        }
    }

    /**
     * Add ElasticSearch model mapping.
     *
     * @param string $model
     *
     * @return bool
     */
    protected function add($model)
    {
        if ($this->hunter->typeExists($model) === true) {
            return $this->error("[{$model}] already mapped");
        }

        $this->output->write("Mapping [{$model}]...");

        $this->hunter->putMapping($model);

        $this->output->writeln("<info>success</info>");
    }

    /**
     * Remove ElasticSearch model mapping.
     *
     * @param  string $model
     *
     * @return bool
     */
    protected function remove($model)
    {
        if ($this->hunter->typeExists($model) === false) {
            return $this->error("[{$model}] not mapped");
        }

        $this->output->write("Removing [{$model}] map...");

        $this->hunter->deleteMapping($model);

        $this->output->writeln("<info>success</info>");
    }
}
