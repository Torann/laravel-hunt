# Laravel Hunt

[![Latest Stable Version](https://poser.pugx.org/torann/laravel-hunt/v/stable.png)](https://packagist.org/packages/torann/laravel-hunt)
[![Total Downloads](https://poser.pugx.org/torann/laravel-hunt/downloads.png)](https://packagist.org/packages/torann/laravel-hunt)
[![Patreon donate button](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://www.patreon.com/torann)
[![Donate weekly to this project using Gratipay](https://img.shields.io/badge/gratipay-donate-yellow.svg)](https://gratipay.com/~torann)
[![Donate to this project using Flattr](https://img.shields.io/badge/flattr-donate-yellow.svg)](https://flattr.com/profile/torann)
[![Donate to this project using Paypal](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4CJA2A97NPYVU)

Unified search for Laravel models using Elasticsearch. Laravel Hunt uses the [official Elasticsearch PHP API](https://github.com/elasticsearch/elasticsearch-php). To get started, you should have a basic knowledge of how Elasticsearch works (indexes, types, mappings, etc).

# Elasticsearch Requirements

You must be running Elasticsearch 5.0 or higher.

## Installation

### Composer

From the command line run:

```
$ composer require torann/laravel-hunt
```

### Laravel

Once installed you need to register the service provider with the application. Open up `config/app.php` and find the `providers` key.

``` php
'providers' => [

    LaravelHunt\LaravelHuntServiceProvider::class,

]
```

### Lumen

For Lumen register the service provider in `bootstrap/app.php`.

``` php
$app->register(LaravelHunt\LaravelHuntServiceProvider::class);
```

### Publish the configurations

Run this on the command line from the root of your project:

```
$ php artisan vendor:publish --provider="LaravelHunt\LaravelHuntServiceProvider" --tag=config
```

A configuration file will be publish to `config/hunt.php`.

### Indexes and Mapping

While you can definitely build your indexes and mapping through the Elasticsearch API, you can also use some helper methods to build indexes and types right from your models.

For custom analyzer, you can set an `settings` property in the `config/hunt.php` file:

```php
[
    'settings' => [
         'number_of_shards' => 1,
         'analysis' => [
             'filter' => [
                 'autocomplete_filter' => [
                     'type' => 'edge_ngram',
                     'min_gram' => 1,
                     'max_gram' => 20,
                 ],
             ],
             'analyzer' => [
                 'autocomplete' => [
                     'type' => 'custom',
                     'tokenizer' => 'standard',
                     'filter' => [
                         'lowercase',
                         'autocomplete_filter',
                     ],
                 ],
             ],
         ],
     ],
]
```

For mapping, you can set a `mappingProperties` property in your model and use some mapping functions from there:

```php
protected $mappingProperties = [
   'title' => [
        'type' => 'string',
        'analyzer' => 'standard'
    ]
];
```

## Artisan Commands

#### `hunt:install`

Create the Elasticsearch index.

#### `hunt:uninstall`

Remove the Elasticsearch index.

#### `hunt:map <model>`

Initialize an Eloquent model map.

Arguments:

```
 model               Name or comma separated names of the model(s) to initialize
```

#### `hunt:import <model>`

Import all the entries in an Eloquent model. This will also initialize the model's map if one is not already set.

Arguments:

```
 model               Name or comma separated names of the model(s) to index
```

#### `hunt:flush <model>`

Flush all of the model's records from the index.

Arguments:

```
 model               Name or comma separated names of the model(s) to index
```

## Indexing

Once you have added the `LaravelHunt\Huntable` trait to a model, all you need to do is save a model instance and it will automatically be added to your index.

```php
$post = new App\Post;

// ...

$post->save();
```

> **Note**: if the model record is already in your index, it will be updated. If it does not exist in the index, it will be added.

## Updating Records

To update an index model, you only need to update the model instance's properties and `save`` the model to your database. Hunt will automatically persist the changes to your search index:

```php
$post = App\Post::find(1);

// Update the post...

$post->save();
```

## Removing Records

To remove a record from your index, simply `delete` the model from the database. This form of removal is even compatible with **soft deleted** models:

```php
$post = App\Post::find(1);

$post->delete();
```

## Searching

You may begin searching a model using the `search` method. The search method accepts a single string that will be used to search your models. You should then chain the `get` method onto the search query to retrieve the Eloquent models that match the given search query:

```php
$posts = App\Post::search('Kitten fluff')->get();
```

Since Hunt searches return a collection of Eloquent models, you may even return the results directly from a route or controller and they will automatically be converted to JSON:

```php
use Illuminate\Http\Request;

Route::get('/search', function (Request $request) {
    return App\Post::search($request->search)->get();
});
```

## Pagination

In addition to retrieving a collection of models, you may paginate your search results using the `paginate` method. This method will return a `Paginator` instance just as if you had paginated a traditional Eloquent query:

```php
$posts = App\Post::search('Kitten fluff')->paginate();
```
You may specify how many models to retrieve per page by passing the amount as the first argument to the `paginate` method:

```php
$posts = App\Post::search('Kitten fluff')->paginate(15);
```
Once you have retrieved the results, you may display the results and render the page links using Blade just as if you had paginated a traditional Eloquent query:

```blade
<div class="container">
    @foreach ($posts as $post)
        {{ $post->title }}
    @endforeach
</div>

{{ $posts->links() }}
```

## Multilingual

> This feature is experimental

Laravel Hunt can support multiple languages by appending the language code to the index type, so when the system performs a search it will only look for data that is on in the current system locale suffixed index type. For this to work the model needs to use the `LaravelHunt\Localized` trait or something similar to it and model needs to have the filed `locale`.

For more information see the config file for more details.