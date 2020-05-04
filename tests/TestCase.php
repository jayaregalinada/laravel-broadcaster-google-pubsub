<?php

use Orchestra\Testbench\TestCase as BaseTestCase;
use Jag\Broadcaster\GooglePubSub\Providers\LaravelPubSubServiceProvider;

class TestCase extends BaseTestCase
{
    public function getPackageProviders($application) : array
    {
        return [
            LaravelPubSubServiceProvider::class,
        ];
    }
}
