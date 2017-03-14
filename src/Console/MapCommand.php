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
                                {model : Name or comma separated names of the model(s) to initialize}
                                {--l|locales= : Single or comma separated locales}';

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
        // Process any locales
        if (empty($locales = $this->getLocales()) === false) {
            foreach ($locales as $locale) {
                $this->setSystemLocale($locale);

                $this->processModels('add');
            }
        }
        else {
            $this->processModels('add');
        }
    }

    /**
     * Add ElasticSearch model mapping.
     *
     * @param string $model
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
}
