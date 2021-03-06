<?php

namespace LaravelHunt;

use ReflectionMethod;
use Illuminate\Support\Arr;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use LaravelHunt\Handlers\AwsSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;

class Hunter
{
    /**
     * Laravel Hunt configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Elasticsearch client instance.
     *
     * @var \Elasticsearch\Client
     */
    protected $elasticsearch;

    /**
     * Multilingual support.
     *
     * @var bool
     */
    protected $multilingual = false;

    /**
     * Create a Hunter instance.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->multilingual = $this->config('multilingual', false);

        $this->elasticsearch = ClientBuilder::fromConfig($this->getElasticSearchConfig());
    }

    /**
     * Check if a type/types exists in an indices.
     *
     * @param mixed $model
     *
     * @return bool
     */
    public function typeExists($model)
    {
        $model = $model instanceof Model ? $model : new $model;

        return $this->client()->indices()->existsType($this->getModelParams($model));
    }

    /**
     * Get mapping
     *
     * @param mixed $model
     *
     * @return array
     */
    public function getMapping($model)
    {
        $model = $model instanceof Model ? $model : new $model;

        return $this->client()->indices()->getMapping($this->getModelParams($model));
    }

    /**
     * Put mapping.
     *
     * @param mixed $model
     *
     * @return array
     */
    public function putMapping($model)
    {
        $model = $model instanceof Model ? $model : new $model;

        $mapping = $this->getModelParams($model);

        $mapping['body'][$this->getModelIndexName($model)] = [
            '_source' => [
                'enabled' => true,
            ],
            'properties' => $model->getMappingProperties(),
        ];

        return $this->client()->indices()->putMapping($mapping);
    }

    /**
     * Add/Update the given models in the index.
     *
     * @param mixed $models
     *
     * @return array
     */
    public function update($models)
    {
        // Ensure it's a collection
        $models = $models instanceof Model
            ? new Collection([$models])
            : $models;

        $body = new Collection();

        $models->each(function ($model) use ($body) {
            $array = $this->getModelDocumentData($model);

            // Skip empties
            if (empty($array)) {
                return null;
            }

            $body->push([
                'index' => [
                    '_index' => $this->getIndexName(),
                    '_type' => $this->getModelIndexName($model, $model->locale),
                    '_id' => $model->getKey(),
                    '_retry_on_conflict' => 3,
                ],
            ]);

            $body->push($array);
        });

        return $this->client()->bulk([
            'refresh' => true,
            'body' => $body->all(),
        ]);
    }

    /**
     * Remove from search index
     *
     * @param mixed $models
     *
     * @return array
     */
    public function remove($models)
    {
        // Ensure it's a collection
        $models = $models instanceof Model
            ? new Collection([$models])
            : $models;

        $body = new Collection();

        $models->each(function ($model) use ($body) {
            $body->push([
                'delete' => [
                    '_index' => $this->getIndexName(),
                    '_type' => $this->getModelIndexName($model, $model->locale),
                    '_id' => $model->getKey(),
                ],
            ]);
        });

        return $this->client()->bulk([
            'refresh' => true,
            'body' => $body->all(),
        ]);
    }

    /**
     * Quick and simple search used for autocompletion.
     *
     * @param string $term
     * @param int    $perPage
     * @param array  $options
     *
     * @return LengthAwarePaginator|array
     */
    public function quickSearch($term, $perPage = 10, array $options = [])
    {
        $results = $this->performSearch($term, array_merge([
            'size' => $perPage,
        ], $options));

        return Arr::get($options, 'group', false)
            ? $this->groupResults($results)
            : $results;
    }

    /**
     * Paginate the given search results.
     *
     * @param string $term
     * @param int    $perPage
     * @param array  $options
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search($term, $perPage = 15, array $options = [])
    {
        // Get current page
        $page = LengthAwarePaginator::resolveCurrentPage();

        // Get search parameters
        $params = $this->getSearchParams($term, array_merge([
            'size' => $perPage,
            'from' => (($page * $perPage) - $perPage),
        ], $options));

        // Make request
        return $this->paginateResults($this->client()->search($params), $page, $perPage, [
            'q' => $term,
        ]);
    }

    /**
     * Perform the given search.
     *
     * @param string $term
     * @param array  $options
     *
     * @return Collection
     */
    public function performSearch($term, array $options = [])
    {
        $results = $this->client()->search($this->getSearchParams($term, $options));

        return $this->hydrateResults($results);
    }

    /**
     * Create collection from results.
     *
     * @param array $result
     *
     * @return Collection
     */
    protected function hydrateResults(array $result)
    {
        $items = array_map(function ($item) {
            return $this->newFromHitBuilder($item);
        }, $result['hits']['hits']);

        return Collection::make($items);
    }

    /**
     * Group Elasticsearch results by table name.
     *
     * @param Collection $results
     *
     * @return array
     */
    protected function groupResults(Collection $results)
    {
        $groups = [];

        $results->each(function ($item) use (&$groups) {
            $groups[$item->getTable()][] = $item;
        });

        return $groups;
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param array $result
     * @param int   $page
     * @param int   $perPage
     * @param array $append
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function paginateResults(array $result, $page, $perPage, array $append = [])
    {
        // Get total number of pages
        $total = $result['hits']['total'];

        // Create pagination instance
        $paginator = (new LengthAwarePaginator($this->hydrateResults($result), $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]));

        return $paginator->appends($append);
    }

    /**
     * New from hit builder.
     *
     * @param array $hit
     *
     * @return Model
     */
    protected function newFromHitBuilder($hit = [])
    {
        // Get model name from source
        if (!($model = Arr::pull($hit['_source'], 'huntable_type'))) return null;

        // Get attributes
        $attributes = (array)$hit['_source'];
        $attributes['result_type'] = basename(str_replace('\\', '/', strtolower($model)));

        // Create model instance from type
        $instance = $this->newFromBuilderRecursive(new $model, $attributes);

        // In addition to setting the attributes
        // from the index, we will set the score as well.
        $instance->documentScore = $hit['_score'];

        return $instance;
    }

    /**
     * Create a new model instance that is existing recursive.
     *
     * @param Model    $model
     * @param array    $attributes
     * @param Relation $parentRelation
     *
     * @return Model
     */
    protected function newFromBuilderRecursive(Model $model, array $attributes = [], Relation $parentRelation = null)
    {
        // Create a new instance of the given model
        $instance = $model->newInstance([], $exists = true);

        // Set the array of model attributes
        $instance->setRawAttributes((array)$attributes, $sync = true);

        // Load relations recursive
        $this->loadRelationsAttributesRecursive($instance);

        // Load pivot
        $this->loadPivotAttribute($instance, $parentRelation);

        return $instance;
    }

    /**
     * Get basic Elasticsearch params.
     *
     * @param array $options
     *
     * @return array
     */
    public function getBasicParams(array $options = [])
    {
        $size = Arr::get($options, 'size', 25);
        $from = Arr::get($options, 'from');
        $types = Arr::get($options, 'types', $this->config('types', '_all'));

        // Check for user specified fields or use default
        if ($types instanceof Model) {
            $types = $this->getModelIndexName($types);
        }

        // Add the locale suffix to types
        else if ($this->multilingual && $types[0] !== '_') {
            $types = implode(',', array_map(function($type) {
                return $type . $this->getMultilingualSuffix();
            }, explode(',', $types)));
        }

        return array_filter([
            'index' => $this->getIndexName(),
            'type' => preg_replace('/\s+/', '', $types),
            'size' => is_numeric($size) ? $size : 25,
            'from' => is_numeric($from) ? $from : null,
        ]);
    }

    /**
     * Set the ignore locale option.
     *
     * @param bool $val
     */
    public function ignoreLocale($val)
    {
        $this->ignore_locale = $val;
    }

    /**
     * Get Elasticsearch search term params.
     *
     * @param string $term
     * @param array  $options
     *
     * @return array
     */
    public function getSearchParams($term, array $options = [])
    {
        // Set parameters
        $params = $this->getBasicParams($options);

        // Get fields option
        $fields = Arr::get($options, 'fields');

        // Check for user specified fields or use default
        if (empty($fields) === true) {
            $fields = $this->config('fields');
        }

        // Add text query if one was entered
        if ($term) {
            if (empty($fields) === false) {
                $params['body']['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query' => $term,
                        'fields' => $fields,
                    ],
                ];
            }
            else {
                $params['body']['query']['match']['_all'][] = $term;
            }
        }

        // Check for must filters
        if ($filter_musts = Arr::get($options, 'filter_musts')) {
            foreach($filter_musts as $filter=>$value) {

                // TODO: This sucks...fix it. Version 2 made it so that
                //       all tags had to be present, not just some of them
                if (is_array($value)) {
                    foreach(array_filter($value) as $k=>$v) {
                        $params['body']['query']['bool']['must'][] = [
                            'term' => [
                                $filter => $v,
                            ],
                        ];
                    }
                }
                else if($value !== '') {
                    $params['body']['query']['bool']['must'][] = [
                        'term' => [
                            $filter => $value,
                        ],
                    ];
                }
            }
        }

        return $params;
    }

    /**
     * Get basic elasticsearch params for model
     *
     * @param Model $model
     *
     * @return array
     */
    protected function getModelParams($model)
    {
        return array_filter([
            'index' => $this->getIndexName(),
            'type' => $this->getModelIndexName($model),
            'id' => $model->getKey(),
        ]);
    }

    /**
     * Get the relations attributes from a model.
     *
     * @param Model $model
     */
    protected function loadRelationsAttributesRecursive(Model $model)
    {
        $attributes = $model->getAttributes();

        foreach ($attributes as $key => $value) {
            if (method_exists($model, $key)) {
                $reflection_method = new ReflectionMethod($model, $key);

                if ($reflection_method->class != 'Illuminate\Database\Eloquent\Model') {
                    $relation = $model->$key();

                    if ($relation instanceof Relation) {
                        // Check if the relation field is single model or collections
                        if (is_null($value) === true || !$this->isMultiLevelArray($value)) {
                            $value = [$value];
                        }

                        $models = $this->hydrateRecursive($relation->getModel(), $value, $relation);

                        // Unset attribute before match relation
                        unset($model[$key]);
                        $relation->match([$model], $models, $key);
                    }
                }
            }
        }
    }

    /**
     * Get the pivot attribute from a model.
     *
     * @param Model    $model
     * @param Relation $parentRelation
     */
    protected function loadPivotAttribute(Model $model, Relation $parentRelation = null)
    {
        foreach ($model->getAttributes() as $key => $value) {
            if ($key === 'pivot') {
                unset($model[$key]);

                $pivot = $parentRelation->newExistingPivot($value);
                $model->setRelation($key, $pivot);
            }
        }
    }

    /**
     * Check if an array is multi-level array like [[id], [id], [id]].
     *
     * For detect if a relation field is single model or collections.
     *
     * @param array $array
     *
     * @return boolean
     */
    protected function isMultiLevelArray(array $array)
    {
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a collection of models from plain arrays recursive.
     *
     * @param Model    $model
     * @param Relation $parentRelation
     * @param array    $items
     *
     * @return Collection
     */
    protected function hydrateRecursive(Model $model, array $items, Relation $parentRelation = null)
    {
        $items = array_map(function ($item) use ($model, $parentRelation) {
            return $this->newFromBuilderRecursive($model, ($item ?: []), $parentRelation);
        }, $items);

        return $model->newCollection($items);
    }

    /**
     * Get the index name for the model.
     *
     * @param Model  $model
     * @param string $locale
     *
     * @return array
     */
    protected function getModelIndexName(Model $model, $locale = null)
    {
        // Get the locale suffix
        $locale = $this->getMultilingualSuffix($locale);

        if (method_exists($model, 'searchableAs') === true) {
            return $model->searchableAs() . $locale;
        }

        return $model->getHunterIndex() . $locale;
    }

    /**
     * If enabled, return the multilingual suffix.
     *
     * @param string $locale
     *
     * @return string
     */
    protected function getMultilingualSuffix($locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        return $this->multilingual
            ? '_' . $locale
            : '';
    }

    /**
     * Get index document data for Laravel Hunt.
     *
     * @param Model $model
     *
     * @return array
     */
    protected function getModelDocumentData(Model $model)
    {
        // Get indexable data from model
        $data = $model->getHunterDocumentData();

        // Append huntable type for polymorphic use
        $data['huntable_type'] = get_class($model);

        return $data;
    }

    /**
     * Get configuration value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Get elasticsearch client
     *
     * @return \Elasticsearch\Client
     */
    public function client()
    {
        return $this->elasticsearch;
    }

    /**
     * Get elasticsearch index name.
     *
     * @return string
     */
    public function getIndexName()
    {
        return $this->config('index', 'default');
    }

    /**
     * Get Elasticsearch settings.
     *
     * @return array
     */
    protected function getElasticSearchConfig()
    {
        // Get all settings
        $settings = $this->config('config');

        // Get a handler if one is set
        if ($config = $this->config('handlers.' . $this->config('config.handler'), null)) {

            // Get handler class
            $handler = Arr::pull($config, 'class');

            // Create handler instance
            $settings['handler'] = new $handler($config);
        }
        else {
            unset($settings['handler']);
        }

        return $settings;
    }
}
