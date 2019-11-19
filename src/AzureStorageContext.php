<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageContext implements Context
{
    /**
     * @var QueueRestProxy
     */
    protected $client;

    /**
     * Configuration options.
     *
     * @var array
     */
    private $config;

    public function __construct(QueueRestProxy $client, array $config = [])
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        $message = new AzureStorageMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);
        return $message;
    }

    public function createTopic(string $topicName): Topic
    {
        $this->client->createQueue($topicName);

        return new AzureStorageDestination($topicName);
    }

    public function createQueue(string $queueName): Queue
    {
        return new AzureStorageDestination($queueName);
    }


    /**
     * @param AzureStorageDestination $queue
     *
     * @throws InvalidDestinationException Thrown, when destination is incompatible with the driver.
     */
    public function deleteQueue(Queue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, AzureStorageDestination::class);

        $this->client->deleteQueue($queue);
    }

    /**
     * @param AzureStorageDestination $topic
     *
     * @throws InvalidDestinationException Thrown, when destination is incompatible with the driver.
     */
    public function deleteTopic(Topic $topic): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($topic, AzureStorageDestination::class);

        $this->client->deleteQueue($topic);
    }

    /**
     * @inheritdoc
     */
    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function createProducer(): Producer
    {
        return new AzureStorageProducer($this->client);
    }

    /**
     * @param AzureStorageDestination $destination
     *
     * @return Consumer
     *
     * @throws InvalidDestinationException Thrown, when destination is incompatible with the driver.
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AzureStorageDestination::class);

        $consumer = new AzureStorageConsumer($this->client, $destination, $this);

        if (isset($this->config['visibility_timeout'])) {
            $consumer->setVisibilityTimeout((int) $this->config['visibility_timeout']);
        }

        return $consumer;
    }

    /**
     * @inheritdoc
     */
    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @inheritdoc
     */
    public function purgeQueue(Queue $queue): void
    {
        throw PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function close(): void
    {
    }
}
