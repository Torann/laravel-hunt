<?php

namespace LaravelHunt;

class Builder
{
    /**
     * Search options.
     *
     * @var array
     */
    public $options = [];

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
     * @param array                               $options
     */
    public function __construct($model, $query, array $options = [])
    {
        $this->model = $model;
        $this->query = $query;
        $this->options = $options;
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
        // Search options
        $options = array_merge($this->options, [
            'size' => $this->limit,
            'types' => $this->model,
        ]);

        return $this->hunter()->performSearch($this->query, $options);
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

        // Search options
        $options = array_merge($this->options, [
            'types' => $this->model,
        ]);

        return $this->hunter()->search($this->query, $perPage, $options);
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