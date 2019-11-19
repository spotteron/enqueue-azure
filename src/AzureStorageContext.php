<?php

declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Exception;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\Exception as InteropException;
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

    /**
     * {@inheritDoc}
     *
     * @throws InteropException Thrown, when topic creation fails.
     */
    public function createTopic(string $topicName): Topic
    {
        try {
            $this->client->createQueue($topicName);
        } catch (Exception $e) {
            $this->handleQueueCreationException($e);
        }

        return new AzureStorageDestination($topicName);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InteropException Thrown, when queue creation fails.
     */
    public function createQueue(string $queueName): Queue
    {
        try {
            $this->client->createQueue($queueName);
        } catch (Exception $e) {
            $this->handleQueueCreationException($e);
        }

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

    /**
     * Handle queue creation exception.
     *
     * @param Exception $e
     *
     * @throws InteropException
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/create-queue4#remarks
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/queue-service-error-codes
     */
    private function handleQueueCreationException(Exception $e): void
    {
        // If we have 409 response code, it indicates a conflict between new queue and queue already present in Azure.
        // This error should be silenced for now.
        // @see https://docs.microsoft.com/en-us/rest/api/storageservices/create-queue4#remarks
        // TODO: Prepare more elaborate checks for error 409, as sometimes it may be desirable to throw it.

        if ($e->getCode() === 409) {
            return;
        }

        // Every other error indicates problems with transport, and, as such, it should be thrown.
        // Error is wrapped with common Queue interop Exception class, because throwing generic Exceptions
        // is bad for error catching upwards.
        // TODO: Create own exception class, for even better error handling possibilities.

        throw new InteropException($e->getMessage(), $e->getCode(), $e);
    }
}
