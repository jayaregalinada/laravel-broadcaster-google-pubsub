<?php

namespace Jag\Broadcaster\GooglePubSub\Providers;

use Illuminate\Support\Str;
use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Jag\Broadcaster\GooglePubSub\Contract\PubSubClientContract;
use Jag\Broadcaster\GooglePubSub\Console\PubSubSubscribeCommand;
use Jag\Broadcaster\GooglePubSub\Exceptions\KeyNotFoundException;
use Jag\Broadcaster\GooglePubSub\Broadcasters\GooglePubSubBroadcaster;
use Jag\Broadcaster\GooglePubSub\Contract\PubSubSubscribeCommandContract;
use const DIRECTORY_SEPARATOR;

class LaravelPubSubServiceProvider extends ServiceProvider
{

    public function register() : void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR,
                ['config', 'google.php']), 'broadcasting.google');
        $this->bindClient();
    }

    protected function bindClient() : void
    {
        $this->app->singleton(PubSubClientContract::class, function ($app) {
            /** @var \Illuminate\Contracts\Config\Repository $config */
            $config = $app->make('config');

            return new PubSubClient([
                'projectId' => $config->get('broadcasting.google.projectId'),
                'keyFilePath' => $this->getKeyContent($config->get('broadcasting.google.keyFilePath')),
            ]);
        });
        $this->app->bind('google-pubsub.client', PubSubClientContract::class);
    }

    public function boot() : void
    {
        $this->bootGooglePubSubBroadcaster($this->app->make(BroadcastManager::class));
    }

    protected function bootGooglePubSubBroadcaster(BroadcastManager $manager) : void
    {
        $manager->extend('google', function ($app) {
            return new GooglePubSubBroadcaster($app->make(PubSubClientContract::class), $app->make('log'));
        });
    }

    public function provides() : array
    {
        return [
            PubSubClientContract::class,
            'google-pubsub.client',
        ];
    }

    /**
     * @param null|string $path
     *
     * @throws \Jag\Broadcaster\GooglePubSub\Exceptions\KeyNotFoundException
     * @return string
     */
    protected function getKeyContent($path = null) : string
    {
        if ($path === null) {
            return $this->getKeyContent(storage_path('key.json'));
        }
        if (Str::startsWith($path, 'storage')) {
            return $this->getKeyContent(storage_path($path));
        }
        if (!file_exists($path)) {
            throw new KeyNotFoundException($path);
        }

        return $path;
    }

}
