<?php

namespace Enqueue\AzureStorage\Tests;

use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\AzureStorage\AzureStorageProducer;
use Enqueue\AzureStorage\AzureStorageMessage;
use Enqueue\AzureStorage\AzureStorageDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Producer;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageResult;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use PHPUnit\Framework\TestCase;

class AzureStorageProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function getProducer():AzureStorageProducer
    {
        return new AzureStorageProducer($this->createQueueRestProxyMock());
    }

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, AzureStorageProducer::class);
    }

    public function testCouldBeConstructedWithQueueRestProxy()
    {
        $producer = $this->getProducer();

        $this->assertInstanceOf(AzureStorageProducer::class, $producer);
    }

    public function testThrowIfDestinationNotAzureStorageDestinationOnSend()
    {
        $producer = $this->getProducer();

        $this->expectException(InvalidDestinationException::class);
        $exceptionMessage = 'The destination must be an instance of Enqueue\AzureStorage\AzureStorageDestination ';
        $exceptionMessage .= 'but got Enqueue\Null\NullQueue.';
        $this->expectExceptionMessage($exceptionMessage);
        $producer->send(new NullQueue('aQueue'), new AzureStorageMessage());
    }

    public function testThrowIfMessageNotAzureStorageMessageOnSend()
    {
        $producer = $this->getProducer();

        $this->expectException(InvalidMessageException::class);
        $exceptionMessage = 'The message must be an instance of Enqueue\AzureStorage\AzureStorageMessage ';
        $exceptionMessage .= 'but it is Enqueue\Null\NullMessage.';
        $this->expectExceptionMessage($exceptionMessage);
        $producer->send(new AzureStorageDestination('aQueue'), new NullMessage());
    }

    public function testShouldCallCreateMessageOnSend()
    {
        $destination = new AzureStorageDestination('aDestination');
        $message = new AzureStorageMessage();

        $queueMessage = $this->createQueueMessageMock();

        $createMessageResult = $this->createMock(CreateMessageResult::class);
        $createMessageResult
            ->expects($this->once())
            ->method('getQueueMessage')
            ->willReturn($queueMessage);

        $queueRestProxy = $this->createQueueRestProxyMock();
        $queueRestProxy
            ->expects($this->once())
            ->method('createMessage')
            ->with('aDestination', $message->getMessageText())
            ->willReturn($createMessageResult)
        ;
        
        $producer = new AzureStorageProducer($queueRestProxy);

        $producer->send($destination, $message);
    }

    public function testGetPriority(): void
    {
        $producer = $this->getProducer();
        $this->assertEquals(null, $producer->getPriority());
    }

    public function testGetDeliveryDelay(): void
    {
        $producer = $this->getProducer();
        $this->assertEquals(null, $producer->getDeliveryDelay());
    }

    public function testSetTtl(): void
    {
        $producer = $this->getProducer();
        $producer->setTimeToLive(100);
        $this->assertEquals(100, $producer->getTimeToLive());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueRestProxy
     */
    private function createQueueRestProxyMock()
    {
        return $this->createMock(QueueRestProxy::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueMessage
     */
    private function createQueueMessageMock()
    {
        $insertionDateMock = $this->createMock(\DateTime::class);
        $insertionDateMock
            ->expects($this->any())
            ->method('getTimestamp')
            ->willReturn(1542809366);
        
        $messageMock = $this->createMock(QueueMessage::class);
        $messageMock
            ->expects($this->any())
            ->method('getMessageId')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getMessageText')
            ->willReturn('aBody');
        $messageMock
            ->expects($this->any())
            ->method('getInsertionDate')
            ->willReturn($insertionDateMock);
        $messageMock
            ->expects($this->any())
            ->method('getDequeueCount')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getDequeueCount')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getExpirationDate')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getExpirationDate')
            ->willReturn('any');
        $messageMock
            ->expects($this->any())
            ->method('getTimeNextVisible')
            ->willReturn('any');
        return $messageMock;
    }

    public function testShouldThrowExceptionOnSetDeliveryDelayWhenDeliveryStrategyIsNotSet()
    {
        $producer = $this->getProducer();

        $this->assertSame($producer, $producer->setDeliveryDelay(null));

        $this->expectException(DeliveryDelayNotSupportedException::class);
        $this->expectExceptionMessage('The provider does not support delivery delay feature');
        $producer->setDeliveryDelay(10000);
    }

    public function testShouldThrowExceptionOnSetPriorityWhenPriorityIsNotSet()
    {
        $producer = $this->getProducer();

        $this->assertSame($producer, $producer->setPriority(null));

        $this->expectException(PriorityNotSupportedException::class);
        $this->expectExceptionMessage('The provider does not support priority feature');
        $producer->setPriority(10000);
    }
}
