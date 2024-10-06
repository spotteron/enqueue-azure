<?php

declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Interop\Queue\Consumer;
use Interop\Queue\Impl\ConsumerPollingTrait;
use Interop\Queue\Impl\ConsumerVisibilityTimeoutTrait;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageConsumer implements Consumer
{
    use ConsumerPollingTrait;
    use ConsumerVisibilityTimeoutTrait;

    /**
     * @var QueueRestProxy
     */
    protected $client;

    protected $queue;

    protected $context;

    public function __construct(QueueRestProxy $client, AzureStorageDestination $queue, AzureStorageContext $context)
    {
        $this->client = $client;
        $this->queue = $queue;
        $this->context = $context;
    }

    /**
     * @inheritdoc
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    /**
     * @inheritdoc
     */
    public function receiveNoWait(): ?Message
    {
        $options = new ListMessagesOptions();
        $options->setNumberOfMessages(1);
        $options->setVisibilityTimeoutInSeconds($this->visibilityTimeout);

        $listMessagesResult = $this->client->listMessages($this->queue->getQueueName(), $options);
        $messages = $listMessagesResult->getQueueMessages();

        if ($messages) {
            $message = $messages[0];

            $messageText = $message->getMessageText();

            // Message is base64 encoded
            $messageText = base64_decode($messageText);

            // and JSON encoded
            $messageArray = json_decode($messageText, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \LogicException(sprintf(
                    'Invalid JSON content for the message. Error was: "%s" and message content is: "%s".',
                    json_last_error_msg(),
                    $messageText
                ));
            }

            if (false === array_key_exists('body', $messageArray)) {
                throw new \LogicException('Missing body in the message.');
            }

            if (false === array_key_exists('properties', $messageArray)) {
                throw new \LogicException('Missing properties in the message.');
            }

            $formattedMessage = new AzureStorageMessage();
            $formattedMessage->setBody($messageArray['body']);
            $formattedMessage->setProperties($messageArray['properties']);
            $formattedMessage->setHeaders([
                'dequeue_count' => $message->getDequeueCount(),
                'expiration_date' => $message->getExpirationDate(),
                'pop_receipt' => $message->getPopReceipt(),
                'next_time_visible' => $message->getTimeNextVisible(),
            ]);
            $formattedMessage->setMessageId($message->getMessageId());
            $formattedMessage->setTimestamp($message->getInsertionDate()->getTimestamp());
            $formattedMessage->setRedelivered($message->getDequeueCount() > 1);


            return $formattedMessage;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function acknowledge(Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, AzureStorageMessage::class);

        $this->client->deleteMessage(
            $this->queue->getQueueName(),
            $message->getMessageId(),
            $message->getHeader('pop_receipt')
        );
    }

    /**
     * @inheritdoc
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, AzureStorageMessage::class);

        // We must acknowledge to remove the message from the queue
        if (false === $requeue) {
            $this->acknowledge($message);
        }
    }
}
