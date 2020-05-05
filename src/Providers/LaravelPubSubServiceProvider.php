<?php

namespace Jag\Broadcaster\GooglePubSub\Providers;

use Illuminate\Support\Str;
use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Broadcasting\BroadcastManager;
use Jag\Exceptions\GooglePubSub\KeyNotFoundException;
use Jag\Contracts\GooglePubSub\PubSubClient as PubSubClientContract;
use Jag\Broadcaster\GooglePubSub\Broadcasters\GooglePubSubBroadcaster;
use const DIRECTORY_SEPARATOR;

class LaravelPubSubServiceProvider extends ServiceProvider
{
    protected const CONFIG_KEY = 'broadcasting.connections.google';

    public function register() : void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR,
                ['config', 'google.php']), self::CONFIG_KEY);
        $this->bindClient();
    }

    protected function bindClient() : void
    {
        $this->app->singleton(PubSubClientContract::class, function ($app) {
            return new PubSubClient($this->createClientConfig($app->make('config')));
        });
        $this->app->bind('google-pubsub.broadcaster.client', PubSubClientContract::class);
    }

    protected function createClientConfig(Repository $config)
    {
        if (!empty($config->get(self::CONFIG_KEY . '.override_config', []))) {
            return array_merge(
                $config->get(self::CONFIG_KEY . '.override_config', []),
                [
                    'projectId' => $config->get(self::CONFIG_KEY . '.projectId'),
                ]
            );
        }

        return [
            'projectId' => $config->get(self::CONFIG_KEY . '.projectId'),
            'keyFilePath' => $this->getKeyContent($config->get(self::CONFIG_KEY . '.keyFilePath')),
        ];
    }

    /**
     * @param string|null $path
     *
     * @throws \Jag\Exceptions\GooglePubSub\KeyNotFoundException
     * @return string
     */
    protected function getKeyContent($path = null) : string
    {
        if ($path === null) {
            return $this->getKeyContent(storage_path('key.json'));
        }
        if (Str::startsWith($path, 'storage')) {
            return $this->getKeyContent(storage_path(substr($path, 8)));
        }
        if (!file_exists($path)) {
            throw new KeyNotFoundException($path);
        }

        return $path;
    }

    public function boot() : void
    {
        $this->bootGooglePubSubBroadcaster($this->app->make(BroadcastManager::class));
    }

    protected function bootGooglePubSubBroadcaster(BroadcastManager $manager) : void
    {
        $manager->extend('google', function ($app) {
            /** @var Repository $config */
            $config = $app->make('config');

            return new GooglePubSubBroadcaster(
                $app->make(PubSubClientContract::class),
                $app->make('log'),
                $config->get(self::CONFIG_KEY . '.auto_create_topic'),
                $app->make($config->get(self::CONFIG_KEY . '.payload_class'))
            );
        });
    }

    public function provides() : array
    {
        return [
            PubSubClientContract::class,
            'google-pubsub.client',
        ];
    }
}
