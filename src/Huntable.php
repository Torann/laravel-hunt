<?php

namespace LaravelHunt;

use Illuminate\Database\Eloquent\Model;

trait Huntable
{
    /**
     * Document score hit score.
     *
     * @var null|int
     */
    public $documentScore = null;

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootHuntable()
    {
        static::observe(ModelObserver::class);
    }

    /**
     * Get mapping properties
     *
     * @return array
     */
    public function getMappingProperties()
    {
        return $this->mappingProperties ?: [];
    }

    /**
     * Dispatch the job to make the model searchable.
     *
     * @return array
     */
    public function addToHunt()
    {
        return $this->getHunter()->update($this);
    }

    /**
     * Dispatch the job to make the model unsearchable.
     *
     * @return array
     */
    public function removeFromHunt()
    {
        return $this->getHunter()->remove($this);
    }

    /**
     * Get the Hunter index name for the model.
     *
     * @return string
     */
    public function getHunterIndex()
    {
        return $this->getTable();
    }

    /**
     * Perform a search against the model's indexed data.
     *
     * @param string $query
     * @param array  $options
     *
     * @return \LaravelHunt\Builder
     */
    public static function search($query, array $options = [])
    {
        return new Builder(new static, $query, $options);
    }

    /**
     * Get Hunter document data for the model.
     *
     * @return array
     */
    public function getHunterDocumentData()
    {
        return $this->toArray();
    }

    /**
     * Get a Hunter for the model.
     *
     * @return Hunter
     */
    public function getHunter()
    {
        return app(Hunter::class);
    }
}
