<?php

namespace LaravelHunt;

use Illuminate\Database\Eloquent\Collection;

class Builder
{
    /**
     * The model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * The query expression.
     *
     * @var string
     */
    public $query;

    /**
     * The "limit" that should be applied to the search.
     *
     * @var int
     */
    public $limit;

    /**
     * Create a new search builder instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $query
     */
    public function __construct($model, $query)
    {
        $this->model = $model;
        $this->query = $query;
    }

    /**
     * Set the "limit" for the search query.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function take($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get the first result from the search.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function first()
    {
        return $this->get()->first();
    }

    /**
     * Get the results of the search.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get()
    {
        return $this->hunter()->performSearch($this->query, [
            'size' => $this->limit,
            'types' => $this->model,
        ]);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int $perPage
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15)
    {
        $perPage = $perPage ?: $this->model->getPerPage();

        return $this->hunter()->search($this->query, $perPage, [
            'types' => $this->model,
        ]);
    }

    /**
     * Get the Hunter to handle the query.
     *
     * @return mixed
     */
    protected function hunter()
    {
        return $this->model->getHunter();
    }
}