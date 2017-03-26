<?php

namespace LaravelHunt;

class ModelObserver
{
    /**
     * Handle the saved event for the model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function saved($model)
    {
        $model->addToHunt();
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
        $this->saved($model);
    }
}