<?php

namespace LaravelHunt;

trait Localized
{
    /**
     * Register eloquent event handlers.
     *
     * @return void
     */
    public static function bootLocalized()
    {
        static::addGlobalScope(new LocalizedScope);
    }
}
