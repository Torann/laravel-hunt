<?php

namespace LaravelHunt\Console;

class ImportCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hunt:import
                                {model : Name or comma separated names of the model(s) to index}
                                {--l|locales= : Single or comma separated locales to index}';

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
        $locales = $this->getLocaleOption();

        foreach ($this->getModelArgument() as $model) {
            if ($model = $this->validateModel($model)) {
                if (empty($locales) === false) {
                    foreach ($locales as $locale) {
                        $this->index($model, $locale);
                    }
                }
                else {
                    $this->index($model);
                }
            }
        }
    }

    /**
     * Index all model entries to ElasticSearch.
     *
     * @param string $model
     * @param string $locale
     *
     * @return bool
     */
    protected function index($model, $locale = '')
    {
        $this->comment("Processing [{$model}]");

        // Get model instance
        $instance = new $model();

        // Check for map and create if missing
        if ($this->hunter->typeExists($instance) === false) {
            $this->line(' - Mapping');
            $this->hunter->putMapping($instance);
        }

        // Get entries by a specific locale
        if ($locale && ($field = $this->hunter->config('locale_field'))) {
            $instance->where($field, $locale);
        }

        // Index model
        $this->line(' - Importing');

        // Import models in chunks
        $instance->chunk(100, function ($models) {
            $this->hunter->update($models);
        });
    }
}
