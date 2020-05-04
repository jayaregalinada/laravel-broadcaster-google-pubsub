<?php

namespace Jag\Broadcaster\GooglePubSub\Broadcasters;

use Psr\Log\LoggerInterface;
use Google\Cloud\PubSub\Message;
use Illuminate\Support\Collection;
use Illuminate\Broadcasting\Channel;
use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Jag\Broadcaster\GooglePubSub\Contract\PubSubClientContract;

class GooglePubSubBroadcaster extends Broadcaster
{
    /**
     * @var \Google\Cloud\PubSub\PubSubClient
     */
    protected $client;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * GooglePubSubBroadcaster constructor.
     *
     * @param \Google\Cloud\PubSub\PubSubClient|PubSubClientContract $client
     * @param \Psr\Log\LoggerInterface          $logger
     */
    public function __construct(PubSubClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @return \Google\Cloud\PubSub\PubSubClient|PubSubClientContract
     */
    public function getClient() : PubSubClientContract
    {
        return $this->client;
    }

    public function setLogger(LoggerInterface $logger) : self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function auth($request)
    {
        // TODO: Implement auth() method.
    }

    /**
     * @inheritDoc
     */
    public function validAuthenticationResponse($request, $result)
    {
        // TODO: Implement validAuthenticationResponse() method.
    }

    /**
     * @inheritDoc
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        foreach ($this->formatChannels($channels) as $channel) {
            $this->publish($channel, $event, $payload);
        }
    }

    protected function formatChannels(array $channels)
    {
        $collection = new Collection($channels);

        return $collection->map(function (Channel $channel) {
            if (property_exists($channel, 'topic')) {
                return (string) $channel->topic;
            }

            return (string) $channel;
        });
    }

    protected function publish($topic, $event, array $payload = [])
    {
        $topic = $this->createTopic($topic);
        $topic->publish($this->createPayload($event, $payload));
    }

    protected function createPayload($event, array $payload = []) : Message
    {
        return new Message([
            'data' => json_encode(compact('event', 'payload'))
        ]);
    }


    protected function createTopic($topic)
    {
        $topic = $this->client->topic($topic);
        if (!$topic->exists()) {
            $topic->create();
        }

        return $topic;
    }
}
