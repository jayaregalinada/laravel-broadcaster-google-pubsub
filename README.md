# Google PubSub (Laravel Broadcaster)
Laravel Broadcaster using [Google PubSub](https://cloud.google.com/pubsub/)
> Currently on development, changes may drastically occur without further notice 

### Requirements
- PHP `^7.1`
- Laravel/Lumen `^7.0`
- [gRPC](https://cloud.google.com/php/grpc) (Optional but increase performance)

### Getting Started

##### Install Composer
```sh
composer install jag/laravel-broadcaster-google-pubsub
```

##### Add Service Provider
Since Laravel 5.5 [Auto Discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) is enabled by default, but in case you disable or uses [Lumen Framework](https://lumen.laravel.com), add the service provider:

On `config/app.php`
```php
...
'providers' => [
    ...
    Jag\Broadcaster\GooglePubSub\Providers\LaravelPubSubServiceProvider::class,
]
...
```
on Lumen however, 

On `bootstrap/app.php`
```php
...
$app->register(Jag\Broadcaster\GooglePubSub\Providers\LaravelPubSubServiceProvider::class);
...
```

### Configuration

##### In Laravel & Lumen
Make sure your `BROADCAST_DRIVER` is `google`

On your `.env`
```
...
BROADCAST_DRIVER=google
GOOGLE_PUBSUB_PROJECT_ID=insert-your-google-project-id-here
GOOGLE_PUBSUB_CREDENTIALS=path/to/your/key.json
```

##### In Lumen
In case you are using Lumen, you need to copy broadcasting configuration usually found at `vendor/laravel/lumen-framework/config/broadcasting.php` to your `config/broadcasting.php`, then add these configuration:

```php

return [
    'default' => env('BROADCAST_DRIVER', 'null'),
    'connections' => [
        // Usually other connections here like pusher, redis, log & null by default
        'google' => [
            'driver' => 'google',
            'projectId' => env('GOOGLE_PUBSUB_PROJECT_ID', env('GOOGLE_PROJECT_ID', env('GCLOUD_PROJECT'))),
            'keyFilePath' => env('GOOGLE_PUBSUB_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS')),
        ]
    ],
];
```

You can also find these configuration at `vendor/jag/laravel-broadcaster-google-pubsub/config/google.php`

> Configuration `GOOGLE_PUBSUB_CREDENTIALS` will default search for `storage/key.json`.

> If you also add like this `GOOGLE_PUBSUB_CREDENTIALS=storage/google-key.json` on your `.env`, it will search for `storage/google-key.json`, just a magic use of `Str::startsWith` [documentation](https://laravel.com/docs/7.x/helpers#method-starts-with).  

## Usage
To use these in your Events, make sure to implement `Illuminate\Contracts\Broadcasting\ShouldBroadcast` and add the topic on `broadcastOn()`.

```php
// App\Events\NewlyCreatedProductEvent.php
...
class NewlyCreatedProductEvent implements ShouldBroadcast {
    ...
    public function broadcastOn()
    {
        return [
            'text-based-topic-name',
            new ProductChannel()
        ];   
    }   
}
```

On your channel, the topic name will be based on channel's name
```php
// App\Broadcasting\ProductChannel
...
class ProductChanel extends Channel
{
    public function __construct()
    {
        return parent::__construct('product-topic');    
    }
}
```

But you can also override this by `$topic` public property.
```php
// App\Broadcasting\ProductChannel
...
class ProductChanel extends Channel
{
    public $topic = 'override-topic-name';

    public function __construct()
    {
        return parent::__construct('product-topic');    
    }
}
```

* * *
###### Created and Developed by [Jay Are Galinada](https://jayaregalinada.github.io)
