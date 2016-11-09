<?php

namespace LaravelHunt;

class ModelObserver
{
    /**
     * Handle the created event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function created($model)
    {
        $model->addToHunt();
    }

    /**
     * Handle the updated event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function updated($model)
    {
        $this->created($model);
    }

    /**
     * Handle the deleted event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function deleted($model)
    {
        $model->removeFromHunt();
    }

    /**
     * Handle the restored event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function restored($model)
    {
        $this->created($model);
    }
}