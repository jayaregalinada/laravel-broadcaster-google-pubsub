<?php

namespace Jag\Broadcaster\GooglePubSub\Broadcasters;

use Psr\Log\LoggerInterface;
use Google\Cloud\PubSub\Topic;
use Google\Cloud\PubSub\Message;
use Illuminate\Support\Collection;
use Illuminate\Broadcasting\Channel;
use Google\Cloud\PubSub\PubSubClient;
use Jag\Contracts\GooglePubSub\Payload;
use Google\Cloud\Core\Exception\NotFoundException;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Jag\Exceptions\GooglePubSub\TopicNotFoundException;

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
     * @var bool
     */
    protected $autoCreateTopic;

    /**
     * @var \Jag\Contracts\GooglePubSub\Payload
     */
    protected $payloadClass;

    /**
     * GooglePubSubBroadcaster constructor.
     *
     * @param \Google\Cloud\PubSub\PubSubClient|\Jag\Contracts\GooglePubSub\PubSubClient $client
     * @param \Psr\Log\LoggerInterface                                                   $logger
     * @param bool                                                                       $autoCreateTopic
     * @param \Jag\Contracts\GooglePubSub\Payload|null                                   $payloadClass
     */
    public function __construct(
        PubSubClient $client,
        LoggerInterface $logger,
        $autoCreateTopic = false,
        Payload $payloadClass = null
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->autoCreateTopic = $autoCreateTopic;
        $this->payloadClass = $payloadClass;
    }

    /**
     * @return \Google\Cloud\PubSub\PubSubClient|\Jag\Contracts\GooglePubSub\PubSubClient
     */
    public function getClient()
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
        return (new Collection($channels))->map(function ($channel) {
            if (is_string($channel)) {
                return $channel;
            }
            if ($channel instanceof Channel) {
                return $this->getTopicFromChannel($channel);
            }

            return (string) $channel;
        });
    }

    protected function getTopicFromChannel(Channel $channel) : string
    {
        if (property_exists($channel, 'topic')) {
            return (string) $channel->topic;
        }

        return (string) $channel;
    }

    /**
     * @param string       $topic
     * @param string|mixed $event
     * @param array        $payload
     *
     * @throws \Jag\Exceptions\GooglePubSub\TopicNotFoundException
     */
    protected function publish(string $topic, $event, array $payload = []) : void
    {
        try {
            $pubSubTopic = $this->createTopic($topic);
            $pubSubTopic->publish($this->createPayload($pubSubTopic, $event, $payload));
        } catch (NotFoundException $e) {
            throw new TopicNotFoundException($topic, $e->getMessage(), $e);
        }
    }

    /**
     * @param string $topic
     *
     * @return \Google\Cloud\PubSub\Topic
     */
    protected function createTopic(string $topic) : Topic
    {
        $pubSubTopic = $this->client->topic($topic);
        if ($this->autoCreateTopic && !$pubSubTopic->exists()) {
            $pubSubTopic->create();
        }

        return $pubSubTopic;
    }

    protected function createPayload(Topic $topic, $event, array $payload = []) : Message
    {
        if ($this->payloadClass !== null) {
            return $this->payloadClass->getMessage($topic, $event, $payload);
        }

        return new Message([
            'data' => json_encode(compact('topic', 'event', 'payload')),
        ]);
    }
}
