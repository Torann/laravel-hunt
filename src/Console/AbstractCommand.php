<?php

namespace LaravelHunt\Console;

use LaravelHunt\Hunter;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

abstract class AbstractCommand extends Command
{
    /**
     * @var \LaravelHunt\Hunter
     */
    protected $hunter;

    /**
     * Create a new console command instance.
     *
     * @param Hunter $hunter
     */
    public function __construct(Hunter $hunter)
    {
        parent::__construct();

        $this->hunter = $hunter;
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
     * Validate model.
     *
     * @param  string $model
     * @return bool
     */
    protected function validateModel($model)
    {
        // Determine the namespace
        $model = ($model[0] !== '\\') ? "\\App\\{$model}" : $model;

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
