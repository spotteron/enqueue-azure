<?php

namespace Enqueue\AzureStorage\Tests;

use Enqueue\Null\NullQueue;
use Enqueue\Null\NullTopic;
use Enqueue\AzureStorage\AzureStorageConsumer;
use Enqueue\AzureStorage\AzureStorageContext;
use Enqueue\AzureStorage\AzureStorageDestination;
use Enqueue\AzureStorage\AzureStorageMessage;
use Enqueue\AzureStorage\AzureStorageProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Queue;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageContextTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(Context::class, AzureStorageContext::class);
    }

    public function testShouldAllowCreateEmptyMessage()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $message = $context->createMessage();

        $this->assertInstanceOf(Message::class, $message);

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $message = $context->createMessage('theBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal']);

        $this->assertInstanceOf(Message::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['aProp' => 'aPropVal'], $message->getProperties());
        $this->assertSame(['aHeader' => 'aHeaderVal'], $message->getHeaders());
    }

    public function testShouldCreateQueue()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $queue = $context->createQueue('aQueue');

        $this->assertInstanceOf(AzureStorageDestination::class, $queue);
        $this->assertSame('aQueue', $queue->getQueueName());
    }

    public function testShouldAllowCreateTopic()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $topic = $context->createTopic('aTopic');

        $this->assertInstanceOf(AzureStorageDestination::class, $topic);
        $this->assertSame('aTopic', $topic->getTopicName());
    }

    public function testThrowNotImplementedOnCreateTmpQueueCall()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $this->expectException(TemporaryQueueNotSupportedException::class);
        $this->expectExceptionMessage('The provider does not support temporary queue feature');

        $context->createTemporaryQueue();
    }

    public function testShouldCreateProducer()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $producer = $context->createProducer();

        $this->assertInstanceOf(AzureStorageProducer::class, $producer);
    }

    public function testShouldThrowIfNotAzureStorageDestinationGivenOnCreateConsumer()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $this->expectException(InvalidDestinationException::class);

        $consumer = $context->createConsumer(new NullQueue('aQueue'));

        $this->assertInstanceOf(AzureStorageConsumer::class, $consumer);
    }

    public function testShouldCreateConsumer()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock(), ['visibility_timeout' => 1]);

        $queue = $context->createQueue('aQueue');

        /** @var AzureStorageConsumer $consumer */
        $consumer = $context->createConsumer($queue);

        $this->assertInstanceOf(AzureStorageConsumer::class, $consumer);
        $this->assertEquals(1, $consumer->getVisibilityTimeout());
    }

    public function testThrowIfNotAzureStorageDestinationGivenOnDeleteQueue()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $this->expectException(InvalidDestinationException::class);
        $context->deleteQueue(new NullQueue('aQueue'));
    }

    public function testShouldAllowDeleteQueue()
    {
        $proxyMock = $this->createQueueRestProxyMock();
        $context = new AzureStorageContext($proxyMock);

        $queue = $context->createQueue('aQueueName');

        // Check, that delete queue command actually is invoked on client.
        $proxyMock
            ->expects($this->once())
            ->method('deleteQueue')
            ->with($queue);

        $context->deleteQueue($queue);
    }

    public function testThrowIfNotAzureStorageDestinationGivenOnDeleteTopic()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $this->expectException(InvalidDestinationException::class);
        $context->deleteTopic(new NullTopic('aTopic'));
    }

    public function testShouldAllowDeleteTopic()
    {
        $proxyMock = $this->createQueueRestProxyMock();
        $context = new AzureStorageContext($proxyMock);

        $topic = $context->createTopic('aTopicName');

        // Check, that delete queue command actually is invoked on client.
        $proxyMock
            ->expects($this->once())
            ->method('deleteQueue')
            ->with($topic);

        $context->deleteQueue($topic);
    }

    public function testShouldReturnNotSupportedSubscriptionConsumerInstance()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $this->expectException(SubscriptionConsumerNotSupportedException::class);
        $this->expectExceptionMessage('The provider does not support subscription consumer.');

        $context->createSubscriptionConsumer();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueRestProxy
     */
    private function createQueueRestProxyMock()
    {
        return $this->createMock(QueueRestProxy::class);
    }

    public function testShouldReturnNotSupportedPurgeQueue()
    {
        $context = new AzureStorageContext($this->createQueueRestProxyMock());

        $this->expectException(PurgeQueueNotSupportedException::class);
        $this->expectExceptionMessage('The provider does not support purge queue.');

        $context->purgeQueue($this->createMock(Queue::class));
    }
}
