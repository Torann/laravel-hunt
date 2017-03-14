<?php

namespace LaravelHunt\Console;

use LaravelHunt\Hunter;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Torann\Localization\LocaleManager;

abstract class AbstractCommand extends Command
{
    /**
     * @var \LaravelHunt\Hunter
     */
    protected $hunter;

    /**
     * Namespace for models.
     *
     * @var string
     */
    protected $models;

    /**
     * Create a new console command instance.
     *
     * @param Hunter $hunter
     */
    public function __construct(Hunter $hunter)
    {
        parent::__construct();

        $this->hunter = $hunter;
        $this->models = config('hunt.model_namespace', '\\App\\');
    }

    /**
     * Perform action model mapping.
     *
     * @param string $action
     */
    protected function processModels($action)
    {
        foreach ($this->getModelArgument() as $model) {
            if ($model = $this->validateModel("{$this->models}{$model}")) {
                $this->$action($model);
            }
        }
    }

    /**
     * Get action argument.
     *
     * @param  array $validActions
     * @return array
     */
    protected function getActionArgument($validActions = [])
    {
        $action = strtolower($this->argument('action'));

        if (in_array($action, $validActions) === false) {
            throw new \RuntimeException("The [{$action}] option does not exist.");
        }

        return $action;
    }

    /**
     * Get model argument.
     *
     * @return array
     */
    protected function getModelArgument()
    {
        $models = explode(',', preg_replace('/\s+/', '', $this->argument('model')));

        return array_map(function($model) {
            $model = array_map(function($m) {
                return Str::studly($m);
            }, explode('\\', $model));

            return implode('\\', $model);
        }, $models);
    }

    /**
     * Get an array of supported locales.
     *
     * @return array|null
     */
    protected function getLocales()
    {
        // Get user specified locales
        if ($locales = $this->option('locales')) {
            return array_filter(explode(',', preg_replace('/\s+/', '', $locales)));
        }

        // Check for package
        if (class_exists('Torann\\Localization\\LocaleManager')) {
            return app(LocaleManager::class)->getSupportedLanguagesKeys();
        }

        return config('hunt.support_locales');
    }

    /**
     * Get an array of supported locales.
     *
     * @param string $locale
     */
    protected function setSystemLocale($locale)
    {
        $this->line('');
        $this->line("System local set to: <info>{$locale}</info>");

        if (class_exists('Torann\\Localization\\LocaleManager')) {
            app(LocaleManager::class)->setLocale($locale);
        }
        else {
            app()->setLocale($locale);
        }
    }

    /**
     * Validate model.
     *
     * @param  string $model
     * @return bool
     */
    protected function validateModel($model)
    {
        // Verify model existence
        if (class_exists($model) === false) {
            $this->error("Model [{$model}] not found");

            return false;
        }

        // Verify model is Elasticsearch ready
        if (method_exists($model, 'getMappingProperties') === false) {
            $this->error("Model [{$model}] is not a valid Laravel Hunt model.");

            return false;
        }

        return $model;
    }
}
